<?php include 'includes/header.php'; ?>

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core/locales/es.js'></script>

<style>
    /* Unos cuantos estilos para que no se vea tan feo, ¿no? */
    #calendario {
        max-width: 1100px;
        margin: 0 auto;
    }
    .fc-event {
        cursor: pointer; /* Que el mouse cambie, pa' que se note que se puede hacer clic */
    }
</style>

<h2>Calendario de Citas</h2>
<p>Aquí cachas todas las citas. Si le haces clic a una, te salen sus datos. Si haces clic en un día libre, agendas una nueva.</p>

<div id="calendario"></div>

<a href="index.php" class="btn-secondary" style="display:inline-block; margin-top: 20px;">Volver al Menú Principal</a>

<script>
    // Cumpa, primero esperamos a que cargue toda la wawa, recién arrancamos con el script.
    document.addEventListener('DOMContentLoaded', function() {
        var calendarioEl = document.getElementById('calendario');

        // Aquí creamos el calendario y le metemos todas las configuraciones.
        var calendario = new FullCalendar.Calendar(calendarioEl, {
            // ----- LA PINTA DEL CALENDARIO -----
            initialView: 'dayGridMonth', // Que arranque mostrando el mes
            locale: 'es', // ¡Hablame en español, pues! Con esto traduce casi todo.
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },

            // ¡AQUÍ ESTÁ LA MAGIA! Con esto traducimos los botones que faltaban.
            buttonText: {
                today:    'Hoy',
                month:    'Mes',
                week:     'Semana',
                day:      'Día'
            },
            
            // ----- DE DÓNDE SACAMOS LOS DATOS -----
            // De aquí jalamos los datos. Le decimos que le pregunte a nuestro API por las citas.
            events: 'api_get_citas.php',

            // ----- QUÉ HACER CUANDO EL USUARIO INTERACTÚA -----
            // Esto se dispara cuando el usuario hace clic en una cita que ya existe.
            eventClick: function(info) {
                let detalles = "Paciente: " + info.event.title + "\n";
                // Usamos 'es-ES' para que la hora se vea bien.
                detalles += "Fecha y Hora: " + info.event.start.toLocaleString('es-ES', { dateStyle: 'long', timeStyle: 'short' }) + "\n";
                detalles += "Estado: " + info.event.extendedProps.estado;
                
                alert(detalles);
            },

            // Y esto, cuando hace clic en un día que no tiene nada.
            dateClick: function(info) {
                if (confirm("¿Quieres agendar una nueva cita para el " + info.dateStr + "?")) {
                    window.location.href = 'agendar_cita.php?fecha=' + info.dateStr;
                }
            }
        });

        // Con esto, ¡pum! Se dibuja el calendario en la pantalla.
        calendario.render();
    });
</script>

<?php include 'includes/footer.php'; ?>