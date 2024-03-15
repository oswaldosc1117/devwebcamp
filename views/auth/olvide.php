<main class="auth">
    <h2 class="auth__heading"><?php echo $titulo; ?></h2>
    <p class="auth__texto">Recupera tu acceso a DevWebcamp</p>

    <?php require_once __DIR__ . '/../templates/alertas.php'; ?>

    <form method="POST" action="/olvide" class="formulario">
        <div class="formulario__campo">
            <label for="email">E-Mail</label>
            <input type="email" name="email" id="email" class="formulario__input" placeholder="Ingresa Correo">
        </div> <!--Cierre de formulario__campo-->

        <input type="submit" class="formulario__submit" value="Enviar Instrucciones">
    </form> <!--Cierre de formulario-->

    <div class="acciones">
        <a href="/login" class="acciones__enlace">¿Ya tienes una Cuenta? Ingresa ahora</a>
        <a href="/registro" class="acciones__enlace">¿Aún no tienes una Cuenta? Crea una</a>
    </div>
</main> <!--Cierre de auth-->

