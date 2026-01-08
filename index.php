<?php
session_start();
require_once __DIR__ . '/auth/AuthMiddleware.php';

// Proteger a página
AuthMiddleware::requireAuth();

// Obter dados do usuário
$user = AcessoCidadaoAuth::getUser();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="style.css">
<title>Credenciamento ESESP</title>
</head>
<body>

<!-- Topo -->
<header class="topo">
    <div class="menu-topo-topo">
        <img src="assets/Imagens/Hamburger menu.png" id="btn-menu" alt="Menu">
    </div>
    <div class="usuario-area">
        <img src="assets/Imagens/OlÃ¡ Credenciado(a).png" alt="UsuÃ¡rio">
        <span>OlÃ¡ Credenciado(a)</span>
    </div>
</header>

<!-- Banner -->
<section class="banner">
    <h1><span class="credenci-grande">CREDENCIAMENTO</span> <span class="credenci-pequeno">ESESP</span></h1>
    <p>FaÃ§a parte do time de credenciados da ESESP</p>
</section>

<!-- Overlay -->
<div id="overlay"></div>

<!-- Menu Lateral -->
<nav id="menu" class="menu">
    <img src="assets/Imagens/X.png" class="btn-fechar" id="btn-fechar" alt="Fechar" title="red icons"></a>

    <ul class="menu-list">
        <li><a href="#">PÃ¡gina Inicial</a></li>
        <li><a href="#">Meu Cadastro</a></li>
        <li><a href="#">Edital</a></li>
        <li><a href="#">Credenciamento</a></li>
        <li><a href="#">Trilhas e Eixos</a></li>
        <li><a href="#">Tutoriais</a></li>
        <li><a href="#">Central de dÃºvidas</a></li>
        <li><a href="#">Fale conosco</a></li>
    </ul>

    <div class="menu-sociais">
        <!--Instagram da Esesp-->
        <a href="https://www.instagram.com/esespgoves/" target="_blank"><img src="assets/Imagens/instagram.png" alt="Instagram"></a>

        <!--Linkedin da Esesp-->
        <a href="https://www.linkedin.com/company/esesp-es/?originalSubdomain=br" target="_blank"><img src="assets/Imagens/linkedin.png" alt="LinkedIn"></a>

        <!--Canal do Youtube da Esesp-->
        <a href="https://www.youtube.com/@esespgoves" target="_blank"><img src="assets/Imagens/Youtube.png" alt="YouTube"></a>
    </div>
</nav>

<!-- Ãrea de Busca -->
<section class="area_busca">
    <div>
        <img class="icone-pesquisa" src="assets/Imagens/Pesquisa.png" alt="Pesquisa">
        <input type="text" placeholder="O que VocÃª estÃ¡ buscando ?">
    </div>
</section>

<!-- BotÃµes Principais -->
<section class="botoes-principais">
    <a class="card-principal" href="#">Leia o edital</a>
    <a class="card-principal" href="#">Credencie-se</a>
    <a class="card-principal" href="#">Acompanhe seu <br> processo</a>
    <a class="card-principal esquerda" href="#">Atualize seu <br> cadastro</a>
</section>

<!-- BotÃµes SecundÃ¡rios -->
<section class="botoes-secundarios">
    <div class="card-azul">
        <div class="card-secundario ">
            <img src="assets/Imagens/Trilhas e Eixos.png">
            <a href="#">Trilhas e Eixos <br> de conhecimento</a>
        </div>
        <div class="card-secundario">
            <img src="assets/Imagens/Tutoriais.png">
            <a href="#">Tutoriais</a>
        </div>
        <div class="card-secundario">
            <img src="assets/Imagens/Fale conosco.png">
            <a href="#">Fale conosco</a>
        </div>
    </div>

    <div class="card-cinza">
        <div class="card-secundario card-menor">
            <img src="assets/Imagens/Logo EAD Esesp Colorida.png">
            <a href="#">ConheÃ§a o EAD da ESESP</a>
        </div>
        <div class="card-secundario card-menor">
            <img src="assets/Imagens/50 anos Esesp.png">
            <a href="#">Saiba mais sobre a ESESP</a>
        </div>
    </div>
</section>

<!-- SessÃ£o de NotÃ­cias -->
<section class="secao-noticias">
    <h2 class="titulo-noticias">NotÃ­cias</h2>

    <div class="noticias-container">

        <!-- CARD 1 -->
        <div class="noticia-card">
            <img src="https://esesp.es.gov.br/Media/esesp/_Profiles/c4d8c6e6/d1eb5fec/esesp-na-estrada-santa-leopoldina-2025-marco-regulatorio-sociedade-civil.jpeg?v=638990839737612140" alt="NotÃ­cia 1">
            <div class="noticia-info">
                <h3>Santa Leopoldina recebe capacitaÃ§Ãµes do projeto â€˜Esesp na Estradaâ€™ para servidores pÃºblicos</h3>
                <p>O municÃ­pio de Santa Leopoldina recebeu, entre os dias 03 e 07 de novembro, mais uma ediÃ§Ã£o do projeto â€œEsesp na Estradaâ€, iniciativa da Escola de ServiÃ§o PÃºblico do EspÃ­rito Santo (Esesp) que leva formaÃ§Ã£o e qualificaÃ§Ã£o aos profissionais que atuam na administraÃ§Ã£o pÃºblica capixaba.</p>
                <a href="https://esesp.es.gov.br/Not%C3%ADcia/santa-leopoldina-recebe-capacitacoes-do-projeto-esesp-na-estrada-para-servidores-publicos" target="_blank" class="btn-noticia">Leia mais</a>
            </div>
        </div>

        <!-- CARD 2 -->
        <div class="noticia-card">
            <img src="https://esesp.es.gov.br/Media/esesp/_Profiles/c4d8c6e6/d1eb5fec/esesp-na-estrada-ponto-belo-2025-03.jpeg?v=638991750774093210" alt="NotÃ­cia 2">
            <div class="noticia-info">
                <h3>â€˜Esesp na Estradaâ€™ passa por Ponto Belo com capacitaÃ§Ãµes para fortalecer a gestÃ£o pÃºblica</h3>
                <p>O municÃ­pio de Ponto Belo recebeu, na Ãºltima quarta-feira (12) e na quinta-feira (13), mais uma ediÃ§Ã£o do projeto â€˜Esesp na Estradaâ€™, iniciativa da Escola de ServiÃ§o PÃºblico do EspÃ­rito Santo (Esesp).</p>
                <a href="https://esesp.es.gov.br/Not%C3%ADcia/esesp-na-estrada-passa-por-ponto-belo-com-capacitacoes-para-fortalecer-a-gestao-publica" target="_blank"  class="btn-noticia">Leia mais</a>
            </div>
        </div>

        <!-- CARD 3 -->
        <div class="noticia-card">
            <img src="https://esesp.es.gov.br/Media/esesp/_Profiles/c4d8c6e6/d1eb5fec/esesp-meio-ambiente-sustentabilidade-gestao-publica.JPG?v=638984688702224990" alt="NotÃ­cia 3">
            <div class="noticia-info">
                <h3>Meio ambiente: Esesp destaca compromisso com sustentabilidade e gestÃ£o pÃºblica</h3>
                <p>Com a realizaÃ§Ã£o da ConferÃªncia das NaÃ§Ãµes Unidas sobre as MudanÃ§as ClimÃ¡ticas (COP30), que comeÃ§ou nessa segunda-feira (10) e segue atÃ© 21 de novembro em BelÃ©m (PA), o mundo volta seus olhos para o debate sobre os desafios climÃ¡ticos e o papel das instituiÃ§Ãµes na construÃ§Ã£o de um futuro sustentÃ¡vel.</p>
                <a href="https://esesp.es.gov.br/Not%C3%ADcia/meio-ambiente-esesp-destaca-compromisso-com-sustentabilidade-e-gestao-publica" target="_blank" class="btn-noticia">Leia mais</a>
            </div>
        </div>

    </div>
</section>


<script src="script.js"></script>
</body>
</html>
