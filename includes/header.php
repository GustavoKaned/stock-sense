<header>

    <div class="header-esq">

        <button class="btn-menu" id="btnMenu"
                aria-label="Abrir menu" aria-expanded="false" aria-controls="navegacaoLateral">
            <i class="fa-solid fa-bars"></i>
        </button>

        <span class="header-titulo"><?= e($tituloPagina ?? "StockSense") ?></span>

    </div>

    <div class="header-dir">
        <button type="button" class="btn btn-neutro" id="alternarTema" aria-label="Ativar modo escuro" aria-pressed="false"><i class="fa-solid fa-moon" aria-hidden="true"></i><span class="tema-texto">Tema</span></button>

        <span class="header-relogio">
            <i class="fa-regular fa-clock"></i>
            <span id="relogio"></span>
        </span>

        <!-- Menu do usuário logado -->
        <div class="usuario" id="menuUsuario">

            <button class="usuario-botao" id="btnUsuario"
                    aria-haspopup="true" aria-expanded="false" aria-controls="usuarioMenu">

                <span class="usuario-inicial">
                    <?= e(mb_strtoupper(mb_substr(empresa_nome(), 0, 1))) ?>
                </span>

                <span class="usuario-texto">
                    <strong><?= e(empresa_nome()) ?></strong>
                    <small><?= e(usuario_nome()) ?></small>
                </span>

                <i class="fa-solid fa-chevron-down"></i>

            </button>

            <div class="usuario-menu" id="usuarioMenu" hidden>

                <div class="usuario-menu-topo">
                    <strong><?= e(usuario_nome()) ?></strong>
                    <small><?= e(empresa_nome()) ?></small>
                </div>

                <a href="logout.php" class="usuario-menu-item">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Sair
                </a>

            </div>

        </div>

    </div>

</header>
