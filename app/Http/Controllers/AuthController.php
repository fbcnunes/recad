<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private ?string $ldapPager = null;

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $username = trim((string) $request->input('username'));
        $password = (string) $request->input('password');

        if ($this->attemptLdap($username, $password)) {
            if ($this->ldapPager) {
                $request->session()->put('ldap_pager', $this->ldapPager);
            } else {
                $request->session()->forget('ldap_pager');
            }
            return redirect()->intended(route('dashboard'));
        }

        if (Auth::attempt(['username' => $username, 'password' => $password])) {
            $request->session()->regenerate();
            $request->session()->forget('ldap_pager');
            return redirect()->intended(route('dashboard'));
        }

        if (Auth::attempt(['email' => $username, 'password' => $password])) {
            $request->session()->regenerate();
            $request->session()->forget('ldap_pager');
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['username' => 'Credenciais inválidas.'])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->forget('ldap_pager');

        return redirect()->route('login');
    }

    private function attemptLdap(string $username, string $password): bool
    {
        $host = config('recad.ldap.host');
        $baseDn = config('recad.ldap.base_dn');
        $bindUser = config('recad.ldap.username');
        $bindPass = config('recad.ldap.password');
        $port = config('recad.ldap.port') ?: 389;

        if (!$host || !$baseDn || !$bindUser || !$bindPass) {
            return false;
        }

        $conn = @ldap_connect($host, $port);
        if (!$conn) {
            return false;
        }

        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

        if (!@ldap_bind($conn, $bindUser, $bindPass)) {
            return false;
        }

        $filter = sprintf('(sAMAccountName=%s)', ldap_escape($username, '', LDAP_ESCAPE_FILTER));
        $search = @ldap_search($conn, $baseDn, $filter, [
            'dn', 'cn', 'displayName', 'mail', 'memberOf', 'sAMAccountName', 'pager',
        ]);
        if (!$search) {
            return false;
        }

        $entries = ldap_get_entries($conn, $search);
        if (!isset($entries['count']) || $entries['count'] < 1) {
            return false;
        }

        $entry = $entries[0];
        $userDn = $entry['dn'] ?? null;
        if (!$userDn) {
            return false;
        }

        if (!@ldap_bind($conn, $userDn, $password)) {
            return false;
        }
        $this->ldapPager = $entry['pager'][0] ?? null;

        $displayName = $entry['displayname'][0] ?? $entry['cn'][0] ?? $username;
        $email = $entry['mail'][0] ?? null;
        if (!$email) {
            $email = Str::lower($username) . '@sead.gov';
        }
        $memberOf = [];
        if (isset($entry['memberof'])) {
            for ($i = 0; $i < $entry['memberof']['count']; $i++) {
                $memberOf[] = $entry['memberof'][$i];
            }
        }

        $role = $this->resolveRole($memberOf);

        $user = User::where('username', $username)->first();
        if (!$user) {
            $user = User::create([
                'username' => $username,
                'name' => $displayName,
                'email' => $email,
                'role' => $role,
                'password' => Hash::make(Str::random(32)),
            ]);
        } else {
            $user->update([
                'name' => $displayName,
                'email' => $email,
                'role' => $role,
            ]);
        }

        Auth::login($user);

        return true;
    }

    private function resolveRole(array $memberOf): string
    {
        $map = config('recad.ldap_groups', []);

        foreach ($map as $role => $groups) {
            foreach ($groups as $dn) {
                foreach ($memberOf as $memberDn) {
                    if (strcasecmp($memberDn, $dn) === 0) {
                        return (string) $role;
                    }
                }
            }
        }

        return 'leitura';
    }
}
