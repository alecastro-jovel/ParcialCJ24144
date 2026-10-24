<!DOCTYPE html>
<html lang="es">
<body style="font-family: Arial, sans-serif; color:#222;">
    <h2 style="color:#1565C0;">¡Tu constancia está lista!</h2>
    <p>Hola <strong>{{ $constancia->nombre_empleado }}</strong>,</p>
    <p>Te informamos que tu solicitud ya fue procesada:</p>
    <ul>
        <li><strong>N.° de solicitud:</strong> {{ $constancia->id_constancias }}</li>
        <li><strong>Tipo:</strong> {{ $constancia->tipo_de_constancia }}</li>
        <li><strong>Cantidad:</strong> {{ $constancia->cantidad }}</li>
        <li><strong>Fecha de solicitud:</strong> {{ $constancia->fecha_de_solicitud->format('d/m/Y') }}</li>
    </ul>
    <p>Puedes pasar a recogerla en el departamento correspondiente.</p>
    <p>Saludos cordiales.</p>
</body>
</html>
