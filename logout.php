<?php

require_once __DIR__ . "/includes/auth.php";

encerrar_sessao();

header("Location: login.php?saiu=1");
exit;
