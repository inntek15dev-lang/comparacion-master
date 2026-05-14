<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'El campo :attribute debe ser aceptado.',
    'active_url' => 'El campo :attribute no es una URL válida.',
    'after' => 'El campo :attribute debe ser una fecha posterior a :date.',
    'after_or_equal' => 'El campo :attribute debe ser una fecha posterior o igual a :date.',
    'alpha' => 'El campo :attribute sólo puede contener letras.',
    'alpha_dash' => 'El campo :attribute sólo puede contener letras, números, guiones y guiones bajos.',
    'alpha_num' => 'El campo :attribute sólo puede contener letras y números.',
    'array' => 'El campo :attribute debe ser un conjunto.',
    'before' => 'El campo :attribute debe ser una fecha anterior a :date.',
    'before_or_equal' => 'El campo :attribute debe ser una fecha anterior o igual a :date.',
    'between' => [
        'numeric' => 'El campo :attribute tiene que estar entre :min y :max.',
        'file' => 'El campo :attribute debe pesar entre :min y :max kilobytes.',
        'string' => 'El campo :attribute tiene que tener entre :min y :max caracteres.',
        'array' => 'El campo :attribute tiene que tener entre :min y :max elementos.',
    ],
    'boolean' => 'El campo :attribute debe tener un valor verdadero o falso.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'date' => 'El campo :attribute no es una fecha válida.',
    'date_equals' => 'El campo :attribute debe ser una fecha igual a :date.',
    'date_format' => 'El campo :attribute no corresponde al formato :format.',
    'different' => 'El campo :attribute y :other deben ser diferentes.',
    'digits' => 'El campo :attribute debe tener :digits dígitos.',
    'digits_between' => 'El campo :attribute debe tener entre :min y :max dígitos.',
    'dimensions' => 'El campo :attribute tiene dimensiones de imagen no válidas.',
    'distinct' => 'El campo :attribute contiene un valor duplicado.',
    'email' => 'El campo :attribute no es un correo válido.',
    'ends_with' => 'El campo :attribute debe finalizar con uno de los siguientes valores: :values',
    'exists' => 'El :attribute seleccionado es inválido.',
    'file' => 'El campo :attribute debe ser un archivo.',
    'filled' => 'El campo :attribute es obligatorio.',
    'gt' => [
        'numeric' => 'El campo :attribute debe ser mayor que :value.',
        'file' => 'El campo :attribute debe tener más de :value kilobytes.',
        'string' => 'El campo :attribute debe tener más de :value caracteres.',
        'array' => 'El campo :attribute debe tener más de :value elementos.',
    ],
    'gte' => [
        'numeric' => 'El campo :attribute debe ser como mínimo :value.',
        'file' => 'El campo :attribute debe tener como mínimo :value kilobytes.',
        'string' => 'El campo :attribute debe tener como mínimo :value caracteres.',
        'array' => 'El campo :attribute debe tener como mínimo :value elementos.',
    ],
    'image' => 'El campo :attribute debe ser una imagen.',
    'in' => 'El :attribute seleccionado no es válido.',
    'in_array' => 'El campo :attribute no existe en :other.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'ip' => 'El campo :attribute debe ser una dirección IP válida.',
    'ipv4' => 'El campo :attribute debe ser un dirección IPv4 válida.',
    'ipv6' => 'El campo :attribute debe ser un dirección IPv6 válida.',
    'json' => 'El campo :attribute debe tener una cadena JSON válida.',
    'lt' => [
        'numeric' => 'El campo :attribute debe ser menor que :value.',
        'file' => 'El campo :attribute debe tener menos de :value kilobytes.',
        'string' => 'El campo :attribute debe tener menos de :value caracteres.',
        'array' => 'El campo :attribute debe tener menos de :value elementos.',
    ],
    'lte' => [
        'numeric' => 'El campo :attribute debe ser como máximo :value.',
        'file' => 'El campo :attribute debe tener como máximo :value kilobytes.',
        'string' => 'El campo :attribute debe tener como máximo :value caracteres.',
        'array' => 'El campo :attribute debe tener como máximo :value elementos.',
    ],
    'max' => [
        'numeric' => 'El campo :attribute no debe ser mayor a :max.',
        'file' => 'El campo :attribute no debe ser mayor que :max kilobytes.',
        'string' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        'array' => 'El campo :attribute no debe tener más de :max elementos.',
    ],
    'mimes' => 'El campo :attribute debe ser un archivo con formato: :values.',
    'mimetypes' => 'El campo :attribute debe ser un archivo con formato: :values.',
    'min' => [
        'numeric' => 'El tamaño de :attribute debe ser de al menos :min.',
        'file' => 'El tamaño de :attribute debe ser de al menos :min kilobytes.',
        'string' => 'El campo :attribute debe contener al menos :min caracteres.',
        'array' => 'El campo :attribute debe tener al menos :min elementos.',
    ],
    'not_in' => 'El :attribute seleccionado no es válido.',
    'not_regex' => 'El formato del campo :attribute no es válido.',
    'numeric' => 'El campo :attribute debe ser numérico.',
    'password' => 'La contraseña es incorrecta.',
    'present' => 'El campo :attribute debe estar presente.',
    'regex' => 'El formato de :attribute es inválido.',
    'required' => 'El campo :attribute es obligatorio.',
    'required_if' => 'El campo :attribute es obligatorio cuando :other es :value.',
    'required_unless' => 'El campo :attribute es obligatorio a menos que :other esté en :values.',
    'required_with' => 'El campo :attribute es obligatorio cuando :values está presente.',
    'required_with_all' => 'El campo :attribute es obligatorio cuando :values están presentes.',
    'required_without' => 'El campo :attribute es obligatorio cuando :values no está presente.',
    'required_without_all' => 'El campo :attribute es obligatorio cuando ninguno de :values está presente.',
    'same' => 'El campo :attribute y :other deben coincidir.',
    'size' => [
        'numeric' => 'El tamaño de :attribute debe ser :size.',
        'file' => 'El tamaño de :attribute debe ser :size kilobytes.',
        'string' => 'El campo :attribute debe contener :size caracteres.',
        'array' => 'El campo :attribute debe contener :size elementos.',
    ],
    'starts_with' => 'El campo :attribute debe comenzar con uno de los siguientes valores: :values',
    'string' => 'El campo :attribute debe ser una cadena de caracteres.',
    'timezone' => 'El campo :attribute debe ser una zona válida.',
    'unique' => 'El campo :attribute ya ha sido registrado.',
    'uploaded' => 'Subir :attribute ha fallado.',
    'url' => 'El formato de :attribute es inválido.',
    'uuid' => 'El campo :attribute debe ser un UUID válido.',

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    'attributes' => [
        'razon_social' => 'Razón Social',
        'nombre_fantasia' => 'Nombre de Fantasía',
        'rut_contratista' => 'RUT Empresa',
        'direccion_calle' => 'Calle',
        'direccion_numero' => 'Número',
        'selected_region_id_contratista' => 'Región',
        'comuna_id' => 'Comuna',
        'telefono_empresa' => 'Teléfono Empresa',
        'email_empresa' => 'Email Empresa',
        'tipo_empresa_legal_id' => 'Tipo Empresa Legal',
        'rubro_id' => 'Rubro',
        'rango_cantidad_trabajadores_id' => 'Rango de Trabajadores',
        'mutualidad_id' => 'Mutualidad',
        'rep_legal_nombres' => 'Nombres del Representante Legal',
        'rep_legal_apellido_paterno' => 'Apellido Paterno del Representante Legal',
        'rep_legal_apellido_materno' => 'Apellido Materno del Representante Legal',
        'rep_legal_rut' => 'RUT del Representante Legal',
        'rep_legal_telefono' => 'Teléfono del Representante Legal',
        'rep_legal_email' => 'Email del Representante Legal',
        'admin_name' => 'Su Nombre Completo',
        'admin_rut_usuario' => 'Su RUT',
        'admin_email' => 'Su Email (usuario)',
        'admin_password' => 'Contraseña',
        'admin_password_confirmation' => 'Confirmación de Contraseña',
    ],

];