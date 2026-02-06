<?php

namespace Database\Seeders;

use App\Models\CertidaoServidor;
use App\Models\ContaBancaria;
use App\Models\ContatoEmergencia;
use App\Models\Dependente;
use App\Models\DocumentoPessoal;
use App\Models\Servidor;
use App\Models\Vinculo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServidorSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('pt_BR');

        for ($i = 1; $i <= 3; $i++) {
            $servidor = Servidor::create([
                'matricula' => str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'nome' => $faker->name,
                'pai' => $faker->name('male'),
                'mae' => $faker->name('female'),
                'data_nascimento' => $faker->date(),
                'estado_civil' => $faker->randomElement(['Solteiro', 'Casado', 'Divorciado']),
                'conjuge_nome' => $faker->optional()->name,
                'naturalidade' => $faker->city,
                'naturalidade_uf' => $faker->stateAbbr,
                'nacionalidade' => 'Brasileira',
                'escolaridade' => $faker->randomElement(['Fundamental', 'Médio', 'Superior']),
                'curso' => $faker->optional()->jobTitle,
                'pos_graduacao' => $faker->optional()->randomElement(['Especialização', 'Mestrado', 'Doutorado']),
                'pos_curso' => $faker->optional()->jobTitle,
                'pos_inicio' => $faker->optional()->date(),
                'pos_fim' => $faker->optional()->date(),
                'pos_carga_horaria' => $faker->optional()->numberBetween(120, 420),
                'sexo' => $faker->randomElement(['Masculino', 'Feminino']),
                'tipo_sanguineo' => $faker->randomElement(['A', 'B', 'AB', 'O']),
                'fator_rh' => $faker->randomElement(['+', '-']),
                'raca_cor' => $faker->randomElement(['Indígena', 'Branca', 'Preta', 'Amarela', 'Parda']),
                'endereco' => $faker->streetAddress,
                'numero' => (string) $faker->buildingNumber,
                'bairro' => $faker->citySuffix,
                'complemento' => $faker->optional()->secondaryAddress,
                'cep' => $faker->postcode,
                'cidade' => $faker->city,
                'cidade_uf' => $faker->stateAbbr,
                'fone_fixo' => $faker->phoneNumber,
                'celular' => $faker->cellphoneNumber,
                'email' => "servidor{$i}@" . Str::lower($faker->freeEmailDomain),
                'plano_saude' => $faker->optional()->company,
            ]);

            DocumentoPessoal::create([
                'servidor_id' => $servidor->id,
                'rg_num' => (string) $faker->randomNumber(8),
                'rg_uf' => $faker->stateAbbr,
                'rg_expedicao' => $faker->date(),
                'cpf' => (string) $faker->unique()->numerify('###########'),
                'id_prof_num' => $faker->optional()->numerify('#######'),
                'id_prof_tipo' => $faker->optional()->randomElement(['CRM', 'OAB', 'CREA']),
                'id_prof_uf' => $faker->stateAbbr,
                'cnh_num' => $faker->optional()->numerify('###########'),
                'cnh_categoria' => $faker->optional()->randomElement(['A', 'B', 'AB', 'C', 'D', 'E']),
                'cnh_validade' => $faker->optional()->date(),
                'cnh_uf' => $faker->stateAbbr,
                'ctps_num' => $faker->optional()->numerify('########'),
                'ctps_serie' => $faker->optional()->numerify('####'),
                'ctps_expedicao' => $faker->optional()->date(),
                'titulo_eleitor_num' => $faker->optional()->numerify('############'),
                'titulo_zona' => $faker->optional()->numerify('###'),
                'titulo_secao' => $faker->optional()->numerify('###'),
                'reservista_num' => $faker->optional()->numerify('########'),
                'reservista_categoria' => $faker->optional()->randomElement(['1ª', '2ª']),
                'reservista_uf' => $faker->stateAbbr,
                'pis_pasep' => $faker->optional()->numerify('###########'),
            ]);

            CertidaoServidor::create([
                'servidor_id' => $servidor->id,
                'tipo' => $faker->randomElement(['Nascimento', 'Casamento']),
                'registro_num' => $faker->optional()->numerify('######'),
                'livro' => $faker->optional()->numerify('###'),
                'folha' => $faker->optional()->numerify('###'),
                'matricula' => $faker->optional()->numerify('################'),
            ]);

            Vinculo::create([
                'servidor_id' => $servidor->id,
                'forma_ingresso' => $faker->randomElement(['Nomeação Livre', 'Concurso', 'Contrato', 'Estágio', 'Cessão']),
                'data_ingresso' => $faker->date(),
                'nomeacao_cessao_data' => $faker->optional()->date(),
                'portaria_num' => $faker->optional()->numerify('###/####'),
                'doe_num' => $faker->optional()->numerify('######'),
                'doe_publicacao_data' => $faker->optional()->date(),
                'cargo_funcao' => $faker->jobTitle,
                'orgao_origem' => $faker->optional()->company,
            ]);

            ContaBancaria::create([
                'servidor_id' => $servidor->id,
                'banco_num' => $faker->numerify('###'),
                'agencia_num' => $faker->numerify('####'),
                'conta_corrente_num' => $faker->numerify('########'),
            ]);

            ContatoEmergencia::create([
                'servidor_id' => $servidor->id,
                'nome' => $faker->name,
                'celular' => $faker->cellphoneNumber,
                'parentesco' => $faker->randomElement(['Pai', 'Mãe', 'Irmão', 'Cônjuge', 'Amigo']),
            ]);

            Dependente::create([
                'servidor_id' => $servidor->id,
                'nome' => $faker->name,
                'parentesco' => $faker->randomElement(['Filho', 'Cônjuge']),
                'nascimento' => $faker->date(),
                'rg_num' => $faker->optional()->numerify('########'),
                'rg_expedicao' => $faker->optional()->date(),
                'cpf' => $faker->optional()->numerify('###########'),
                'certidao_tipo' => $faker->randomElement(['Nascimento', 'Casamento', 'União Estável']),
                'sexo' => $faker->randomElement(['Masculino', 'Feminino']),
                'tipo_dependente' => $faker->randomElement(['SF', 'IRPF', 'Plano de Saúde']),
            ]);
        }
    }
}
