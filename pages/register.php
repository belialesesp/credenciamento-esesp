<?php
// pages/register.php - Redirect page since we no longer use public registration

require_once '../components/header.php';
?>

<div class="container register-container text-center">
    <h1 class="main-title">Cadastro de Usuários</h1>
    
    <div class="alert alert-info">
        <h4>Sistema de Cadastro Atualizado</h4>
        <p>O cadastro de novos usuários agora é feito automaticamente através dos formulários de credenciamento.</p>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Já possui cadastro?</h5>
                    <p class="card-text">Se você já preencheu um formulário de credenciamento, você já possui acesso ao sistema.</p>
                    <a href="login.php" class="btn btn-primary">Fazer Login</a>
                    <p class="mt-2"><small>Use seu CPF como usuário e senha</small></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Precisa se credenciar?</h5>
                    <p class="card-text">Acesse o formulário apropriado para seu tipo de cadastro.</p>
                    <a href="/credenciamento/" class="btn btn-success">Formulários de Credenciamento</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <p class="text-muted">
            Após preencher o formulário de credenciamento, seu acesso será criado automaticamente.<br>
            Use seu CPF (apenas números) como usuário e senha inicial.
        </p>
    </div>
</div>

<?php
require_once '../components/footer.php';
?>