@php
    /** @var string $aba */
    /** @var array $confirmacoes */
    $conf = $confirmacoes[$aba] ?? null;
@endphp

<div class="card" style="margin-top:16px; border-style:dashed;">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
        <strong>Confirmação desta aba</strong>
        @if($conf)
            <span class="badge" style="background:#dcfce7; color:#166534;">Confirmado</span>
        @else
            <span class="badge" style="background:#fee2e2; color:#991b1b;">Pendente</span>
        @endif
    </div>

    @if($conf)
        <div class="muted" style="margin-top:8px;">
            Confirmado em {{ optional($conf->confirmado_em)->format('d/m/Y H:i') }}.
        </div>
        <form method="post" action="{{ route('servidores.self.unconfirm', ['aba' => $aba]) }}" style="margin-top:12px;">
            @csrf
            <input type="hidden" name="_active_tab" class="active-tab-field" value="{{ $aba }}">
            <button class="btn secondary" type="submit">Desconfirmar</button>
        </form>
    @else
        <div class="muted" style="margin-top:8px;">
            Confirme que os dados desta aba estao corretos.
        </div>
        <form method="post" action="{{ route('servidores.self.confirm', ['aba' => $aba]) }}" style="margin-top:12px;">
            @csrf
            <input type="hidden" name="_active_tab" class="active-tab-field" value="{{ $aba }}">
            <button class="btn" type="submit">Confirmar aba</button>
        </form>
    @endif
</div>

