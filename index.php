<?php
/**
 * Página Inicial - Sistema de Credenciamento ESESP
 * Protegido por Acesso Cidadão
 */

session_start();
require_once __DIR__ . '/auth/AuthMiddleware.php';

// Proteger a página - redireciona para login se não autenticado
AuthMiddleware::requireAuth();

// Obter dados do usuário autenticado via Acesso Cidadão
$user = AcessoCidadaoAuth::getUser();

// Nome do usuário para exibição
$nomeUsuario = $user['apelido'] ?? $user['nome'] ?? 'Credenciado(a)';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css">
    <title>Credenciamento ESESP</title>
</head>
<body>

<!-- Topo -->
<header class="topo">
    <div class="menu-topo-topo">
        <img src="assets/Imagens/Hamburger menu.png" id="btn-menu" alt="Menu">
    </div>
    <div class="usuario-area">
        <img src="assets/Imagens/Olá Credenciado(a).png" alt="Usuário">
        <span>Olá <?= htmlspecialchars($nomeUsuario) ?></span>
    </div>
</header>

<!-- Banner -->
<section class="banner">
    <h1><span class="credenci-grande">CREDENCIAMENTO</span> <span class="credenci-pequeno">ESESP</span></h1>
    <p>Faça parte do time de credenciados da ESESP</p>
</section>

<!-- Overlay -->
<div id="overlay"></div>

<!-- Menu Lateral -->
<nav id="menu" class="menu">
    <img src="assets/Imagens/X.png" class="btn-fechar" id="btn-fechar" alt="Fechar" title="Fechar menu">

    <ul class="menu-list">
        <li><a href="/">Página Inicial</a></li>
        <li><a href="/pages/perfil.php">Meu Cadastro</a></li>
        <li><a href="/pages/edital.php">Edital</a></li>
        <li><a href="/pages/credenciamento.php">Credenciamento</a></li>
        <li><a href="/pages/trilhas.php">Trilhas e Eixos</a></li>
        <li><a href="/pages/tutoriais.php">Tutoriais</a></li>
        <li><a href="/pages/duvidas.php">Central de dúvidas</a></li>
        <li><a href="/pages/contato.php">Fale conosco</a></li>
        <li><a href="/logout.php" class="menu-logout">Sair do Sistema</a></li>
    </ul>

    <div class="menu-sociais">
        <!--Instagram da Esesp-->
        <a href="https://www.instagram.com/esespgoves/" target="_blank">
            <img src="assets/Imagens/instagram.png" alt="Instagram">
        </a>

        <!--Linkedin da Esesp-->
        <a href="https://www.linkedin.com/company/esesp-es/?originalSubdomain=br" target="_blank">
            <img src="assets/Imagens/linkedin.png" alt="LinkedIn">
        </a>

        <!--Canal do Youtube da Esesp-->
        <a href="https://www.youtube.com/@esespgoves" target="_blank">
            <img src="assets/Imagens/Youtube.png" alt="YouTube">
        </a>
    </div>
</nav>

<!-- Área de Busca -->
<section class="area_busca">
    <div>
        <img class="icone-pesquisa" src="assets/Imagens/Pesquisa.png" alt="Pesquisa">
        <input type="text" placeholder="O que você está buscando?">
    </div>
</section>

<!-- Botões Principais -->
<section class="botoes-principais">
    <a class="card-principal" href="/pages/edital.php">Leia o edital</a>
    <a class="card-principal" href="/pages/credenciamento.php">Credencie-se</a>
    <a class="card-principal" href="/pages/acompanhamento.php">Acompanhe seu<br>processo</a>
    <a class="card-principal esquerda" href="/pages/perfil.php">Atualize seu<br>cadastro</a>
</section>

<!-- Botões Secundários -->
<section class="botoes-secundarios">
    <div class="card-azul">
        <div class="card-secundario">
            <img src="assets/Imagens/Trilhas e Eixos.png" alt="Trilhas">
            <a href="/pages/trilhas.php">Trilhas e Eixos<br>de conhecimento</a>
        </div>
        <div class="card-secundario">
            <img src="assets/Imagens/Tutoriais.png" alt="Tutoriais">
            <a href="/pages/tutoriais.php">Tutoriais</a>
        </div>
        <div class="card-secundario">
            <img src="assets/Imagens/Fale conosco.png" alt="Contato">
            <a href="/pages/contato.php">Fale conosco</a>
        </div>
    </div>

    <div class="card-cinza">
        <div class="card-secundario card-menor">
            <img src="assets/Imagens/Logo EAD Esesp Colorida.png" alt="EAD">
            <a href="https://ead.esesp.es.gov.br" target="_blank">Conheça o EAD da ESESP</a>
        </div>
        <div class="card-secundario card-menor">
            <img src="assets/Imagens/50 anos Esesp.png" alt="Sobre">
            <a href="https://esesp.es.gov.br" target="_blank">Saiba mais sobre a ESESP</a>
        </div>
    </div>
</section>

<!-- Seção de Notícias -->
<section class="secao-noticias">
    <h2 class="titulo-noticias">Notícias</h2>

    <div class="noticias-container">

        <!-- CARD 1 -->
        <div class="noticia-card">
            <img src="https://esesp.es.gov.br/Media/esesp/_Profiles/c4d8c6e6/d1eb5fec/esesp-na-estrada-santa-leopoldina-2025-marco-regulatorio-sociedade-civil.jpeg?v=638990839737612140" alt="Notícia 1">
            <div class="noticia-info">
                <h3>Santa Leopoldina recebe capacitações do projeto 'Esesp na Estrada' para servidores públicos</h3>
                <p>O município de Santa Leopoldina recebeu, entre os dias 03 e 07 de novembro, mais uma edição do projeto "Esesp na Estrada", iniciativa da Escola de Serviço Público do Espírito Santo (Esesp) que leva formação e qualificação aos profissionais que atuam na administração pública capixaba.</p>
                <a href="https://esesp.es.gov.br/Notícia/santa-leopoldina-recebe-capacitacoes-do-projeto-esesp-na-estrada-para-servidores-publicos" target="_blank" class="btn-noticia">Leia mais</a>
            </div>
        </div>

        <!-- CARD 2 -->
        <div class="noticia-card">
            <img src="https://esesp.es.gov.br/Media/esesp/_Profiles/c4d8c6e6/d1eb5fec/esesp-na-estrada-ponto-belo-2025-03.jpeg?v=638991750774093210" alt="Notícia 2">
            <div class="noticia-info">
                <h3>'Esesp na Estrada' passa por Ponto Belo com capacitações para fortalecer a gestão pública</h3>
                <p>O município de Ponto Belo recebeu, na última quarta-feira (12) e na quinta-feira (13), mais uma edição do projeto 'Esesp na Estrada', iniciativa da Escola de Serviço Público do Espírito Santo (Esesp).</p>
                <a href="https://esesp.es.gov.br/Notícia/esesp-na-estrada-passa-por-ponto-belo-com-capacitacoes-para-fortalecer-a-gestao-publica" target="_blank" class="btn-noticia">Leia mais</a>
            </div>
        </div>

        <!-- CARD 3 -->
        <div class="noticia-card">
            <img src="https://esesp.es.gov.br/Media/esesp/_Profiles/c4d8c6e6/d1eb5fec/esesp-meio-ambiente-sustentabilidade-gestao-publica.JPG?v=638984688702224990" alt="Notícia 3">
            <div class="noticia-info">
                <h3>Meio ambiente: Esesp destaca compromisso com sustentabilidade e gestão pública</h3>
                <p>Com a realização da Conferência das Nações Unidas sobre as Mudanças Climáticas (COP30), que começou nessa segunda-feira (10) e segue até 21 de novembro em Belém (PA), o mundo volta seus olhos para o debate sobre os desafios climáticos e o papel das instituições na construção de um futuro sustentável.</p>
                <a href="https://esesp.es.gov.br/Notícia/meio-ambiente-esesp-destaca-compromisso-com-sustentabilidade-e-gestao-publica" target="_blank" class="btn-noticia">Leia mais</a>
            </div>
        </div>

    </div>
</section>

<script src="assets/script.js"></script>

</body>
</html>