<?php

return [
    'escolaridade' => ['Fundamental', 'Médio', 'Superior'],
    'sexo' => ['Masculino', 'Feminino'],
    'tipo_sanguineo' => ['A', 'B', 'AB', 'O'],
    'fator_rh' => ['+', '-'],
    'raca_cor' => ['Indígena', 'Branca', 'Preta', 'Amarela', 'Parda'],
    'estado_civil' => ['Solteiro', 'Casado', 'Divorciado', 'Viúvo', 'União Estável'],
    'certidao_tipo' => ['Nascimento', 'Casamento', 'União Estável'],
    'forma_ingresso' => ['Nomeação Livre', 'Nomeação Concurso', 'Contrato', 'Estágio', 'Cessão'],
    'dependente_tipo' => ['SF', 'IRPF', 'Plano de Saúde'],
    'ldap' => [
        'host' => env('CONN_HOST', env('LDAP_HOST')),
        'base_dn' => env('CONN_BASE_DN', env('LDAP_BASE_DN')),
        'username' => env('CONN_USERNAME', env('LDAP_USERNAME')),
        'password' => env('CONN_PASSWORD', env('LDAP_PASSWORD')),
        'port' => (int) env('CONN_PORT', env('LDAP_PORT', 389)),
    ],
    'ldap_groups' => [
        'leitura' => [
            env('LDAP_GROUP_LEITURA', 'CN=Users,DC=sead,DC=gov'),
        ],
    ],
];
