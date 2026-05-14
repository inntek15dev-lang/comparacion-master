<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Verificación</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .header { text-align: center; margin-bottom: 20px; }
        .code { font-size: 28px; font-weight: bold; text-align: center; letter-spacing: 4px; padding: 15px; background-color: #f2f2f2; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; font-size: 12px; color: #777; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Verificación de Inicio de Sesión en OVALCONTROL</h2>
        </div>
        <p>Hola,</p>
        <p>Se ha detectado un intento de inicio de sesión desde un nuevo dispositivo o navegador. Para completar el acceso, por favor utiliza el siguiente código de verificación:</p>
        <div class="code">{{ $code }}</div>
        <p>Este código expirará en 15 minutos. Si no has intentado iniciar sesión, puedes ignorar este correo electrónico de forma segura.</p>
        <p>Gracias,<br>El equipo de OVALCONTROL</p>
        <div class="footer">
            <p>&copy; {{ date('Y') }} OVALCONTROL. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>