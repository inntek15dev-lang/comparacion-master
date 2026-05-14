@extends('components.public') {{-- Especificamos la plantilla exacta que vamos a usar --}}

@section('content') {{-- Marcamos el inicio del contenido de ESTA página --}}

    <div class="py-16 sm:py-24">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                
                {{-- Columna de Texto --}}
                <div class="space-y-6">
                    <h1 class="text-5xl font-bold tracking-tight">Quiénes Somos</h1>
                    <p class="text-gray-300 leading-relaxed">
                        OVAL Latam es una empresa que nace en Chile y expande su presencia hacia Latinoamérica, con operaciones en Uruguay y una apertura de mercados en Colombia y Perú. Nos posicionamos como un socio estratégico para empresas líderes en la región, destacándonos en el control y gestión de empresas proveedoras de bienes y servicios, asegurando información precisa y oportuna a empresas principales.
                    </p>
                    <p class="text-gray-300 leading-relaxed">
                        Nos enfocamos en ofrecer un servicio personalizado considerando las necesidades de cada cliente, con ello garantizamos la entrega de un valor estratégico al facilitar soluciones prácticas, integrales y efectivas que optimizan la relación entre empresas principales y contratistas, contribuyendo al éxito de nuestros clientes.
                    </p>
                    <p class="text-gray-300 leading-relaxed">
                        Tanto nuestros procesos como nuestra infraestructura tecnológica cuentan con certificaciones internacionales que garantizan la continuidad de las operaciones y los máximos estándares de seguridad de la información.
                    </p>
                </div>

                {{-- Columna del Mapa --}}
                <div>
                    <img src="{{ asset('images/mapa-latam.png') }}" alt="Mapa de operaciones en Latinoamérica" class="w-full h-auto">
                </div>

            </div>
        </div>
    </div>

@endsection {{-- Marcamos el final del contenido --}}