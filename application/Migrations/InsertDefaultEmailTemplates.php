<?php

namespace Agencia\Close\Migrations;

use PDO;
use PDOException;

class InsertDefaultEmailTemplates extends Migration
{
    public function up(): void
    {
        // Buscar todas as empresas do sistema
        try {
            $stmt = $this->conn->query("SELECT DISTINCT empresa FROM usuarios WHERE empresa IS NOT NULL AND empresa > 0");
            $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $empresas = [];
        }

        if (empty($empresas)) {
            // Se não houver empresas, criar para empresa 0 (padrão)
            $empresas = [['empresa' => 0]];
        }

        $templates = $this->getTemplatesPadrao();

        foreach ($empresas as $empresaData) {
            $empresa = (int)($empresaData['empresa'] ?? 0);

            foreach ($templates as $tipo => $template) {
                // Verificar se já existe template para este tipo e empresa
                try {
                    $stmt = $this->conn->prepare("SELECT id FROM email_templates WHERE empresa = ? AND tipo = ?");
                    $stmt->execute([$empresa, $tipo]);
                    $existe = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    $existe = false;
                }

                if (!$existe) {
                    // Inserir template padrão
                    try {
                        $stmt = $this->conn->prepare("INSERT INTO `email_templates` (`empresa`, `tipo`, `assunto`, `corpo`, `ativo`) VALUES (?, ?, ?, ?, 1)");
                        $stmt->execute([
                            $empresa,
                            $tipo,
                            $template['assunto'],
                            $template['corpo']
                        ]);
                    } catch (PDOException $e) {
                        // Log do erro mas continua
                        error_log("Erro ao inserir template {$tipo} para empresa {$empresa}: " . $e->getMessage());
                    }
                }
            }
        }
    }

    public function down(): void
    {
        // Remove todos os templates padrão (opcional - pode comentar se não quiser remover)
        $templates = array_keys($this->getTemplatesPadrao());
        
        try {
            $placeholders = implode(',', array_fill(0, count($templates), '?'));
            $stmt = $this->conn->prepare("DELETE FROM `email_templates` WHERE tipo IN ({$placeholders})");
            $stmt->execute($templates);
        } catch (PDOException $e) {
            error_log("Erro ao remover templates padrão: " . $e->getMessage());
        }
    }

    private function getTemplatesPadrao(): array
    {
        return [
            'quesito_criar' => [
                'assunto' => 'Novo Quesito Cadastrado - {{titulo}}',
                'corpo' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #d6a220; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; margin-top: 20px; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #d6a220; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{titulo}}</h2>
        </div>
        <div class="content">
            <p>Olá,</p>
            <p>{{mensagem}}</p>
            <div style="margin-top: 20px;">
                {{detalhes}}
            </div>
            <a href="{{url}}" class="btn">Ver Detalhes</a>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático do sistema. Por favor, não responda.</p>
        </div>
    </div>
</body>
</html>'
            ],
            'quesito_editar' => [
                'assunto' => 'Quesito Atualizado - {{titulo}}',
                'corpo' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #d6a220; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; margin-top: 20px; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #d6a220; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{titulo}}</h2>
        </div>
        <div class="content">
            <p>Olá,</p>
            <p>{{mensagem}}</p>
            <div style="margin-top: 20px;">
                {{detalhes}}
            </div>
            <a href="{{url}}" class="btn">Ver Detalhes</a>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático do sistema. Por favor, não responda.</p>
        </div>
    </div>
</body>
</html>'
            ],
            'quesito_enviar_cliente' => [
                'assunto' => 'Atualização de Quesito - {{titulo}}',
                'corpo' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #d6a220; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; margin-top: 20px; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{titulo}}</h2>
        </div>
        <div class="content">
            <p>Prezado(a) Cliente,</p>
            <p>{{mensagem}}</p>
            <div style="margin-top: 20px;">
                {{detalhes}}
            </div>
            <p style="margin-top: 20px;">Atenciosamente,<br>Equipe de Perícias</p>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático. Por favor, não responda.</p>
        </div>
    </div>
</body>
</html>'
            ],
            'parecer_criar' => [
                'assunto' => 'Novo Parecer Cadastrado - {{titulo}}',
                'corpo' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #28a745; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; margin-top: 20px; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{titulo}}</h2>
        </div>
        <div class="content">
            <p>Olá,</p>
            <p>{{mensagem}}</p>
            <div style="margin-top: 20px;">
                {{detalhes}}
            </div>
            <a href="{{url}}" class="btn">Ver Detalhes</a>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático do sistema. Por favor, não responda.</p>
        </div>
    </div>
</body>
</html>'
            ],
            'parecer_editar' => [
                'assunto' => 'Parecer Atualizado - {{titulo}}',
                'corpo' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #28a745; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; margin-top: 20px; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{titulo}}</h2>
        </div>
        <div class="content">
            <p>Olá,</p>
            <p>{{mensagem}}</p>
            <div style="margin-top: 20px;">
                {{detalhes}}
            </div>
            <a href="{{url}}" class="btn">Ver Detalhes</a>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático do sistema. Por favor, não responda.</p>
        </div>
    </div>
</body>
</html>'
            ],
            'manifestacao_criar' => [
                'assunto' => 'Nova Manifestação/Impugnação Cadastrada - {{titulo}}',
                'corpo' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #45ADDA; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; margin-top: 20px; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #45ADDA; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{titulo}}</h2>
        </div>
        <div class="content">
            <p>Olá,</p>
            <p>{{mensagem}}</p>
            <div style="margin-top: 20px;">
                {{detalhes}}
            </div>
            <a href="{{url}}" class="btn">Ver Detalhes</a>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático do sistema. Por favor, não responda.</p>
        </div>
    </div>
</body>
</html>'
            ],
            'manifestacao_editar' => [
                'assunto' => 'Manifestação/Impugnação Atualizada - {{titulo}}',
                'corpo' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #45ADDA; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; margin-top: 20px; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #45ADDA; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{titulo}}</h2>
        </div>
        <div class="content">
            <p>Olá,</p>
            <p>{{mensagem}}</p>
            <div style="margin-top: 20px;">
                {{detalhes}}
            </div>
            <a href="{{url}}" class="btn">Ver Detalhes</a>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático do sistema. Por favor, não responda.</p>
        </div>
    </div>
</body>
</html>'
            ],
            'perito_criar' => [
                'assunto' => 'Novo Perito Cadastrado - {{titulo}}',
                'corpo' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #214BB8; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; margin-top: 20px; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #214BB8; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{titulo}}</h2>
        </div>
        <div class="content">
            <p>Olá,</p>
            <p>{{mensagem}}</p>
            <div style="margin-top: 20px;">
                {{detalhes}}
            </div>
            <a href="{{url}}" class="btn">Ver Detalhes</a>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático do sistema. Por favor, não responda.</p>
        </div>
    </div>
</body>
</html>'
            ],
            'perito_editar' => [
                'assunto' => 'Perito Atualizado - {{titulo}}',
                'corpo' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #214BB8; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; margin-top: 20px; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #214BB8; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{titulo}}</h2>
        </div>
        <div class="content">
            <p>Olá,</p>
            <p>{{mensagem}}</p>
            <div style="margin-top: 20px;">
                {{detalhes}}
            </div>
            <a href="{{url}}" class="btn">Ver Detalhes</a>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático do sistema. Por favor, não responda.</p>
        </div>
    </div>
</body>
</html>'
            ],
            'agendamento_criar' => [
                'assunto' => 'Novo Agendamento Criado - {{titulo}}',
                'corpo' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #FFC700; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; margin-top: 20px; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #FFC700; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{titulo}}</h2>
        </div>
        <div class="content">
            <p>Olá,</p>
            <p>{{mensagem}}</p>
            <div style="margin-top: 20px;">
                {{detalhes}}
            </div>
            <a href="{{url}}" class="btn">Ver Detalhes</a>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático do sistema. Por favor, não responda.</p>
        </div>
    </div>
</body>
</html>'
            ],
            'agendamento_editar' => [
                'assunto' => 'Agendamento Atualizado - {{titulo}}',
                'corpo' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #FFC700; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; margin-top: 20px; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #FFC700; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{titulo}}</h2>
        </div>
        <div class="content">
            <p>Olá,</p>
            <p>{{mensagem}}</p>
            <div style="margin-top: 20px;">
                {{detalhes}}
            </div>
            <a href="{{url}}" class="btn">Ver Detalhes</a>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático do sistema. Por favor, não responda.</p>
        </div>
    </div>
</body>
</html>'
            ],
            'reclamada_criar' => [
                'assunto' => 'Nova Reclamada Cadastrada - {{titulo}}',
                'corpo' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #6c757d; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; margin-top: 20px; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{titulo}}</h2>
        </div>
        <div class="content">
            <p>Olá,</p>
            <p>{{mensagem}}</p>
            <div style="margin-top: 20px;">
                {{detalhes}}
            </div>
            <a href="{{url}}" class="btn">Ver Detalhes</a>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático do sistema. Por favor, não responda.</p>
        </div>
    </div>
</body>
</html>'
            ],
            'reclamada_editar' => [
                'assunto' => 'Reclamada Atualizada - {{titulo}}',
                'corpo' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #6c757d; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; margin-top: 20px; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{titulo}}</h2>
        </div>
        <div class="content">
            <p>Olá,</p>
            <p>{{mensagem}}</p>
            <div style="margin-top: 20px;">
                {{detalhes}}
            </div>
            <a href="{{url}}" class="btn">Ver Detalhes</a>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático do sistema. Por favor, não responda.</p>
        </div>
    </div>
</body>
</html>'
            ],
            'reclamante_criar' => [
                'assunto' => 'Novo Reclamante Cadastrado - {{titulo}}',
                'corpo' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #6c757d; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; margin-top: 20px; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{titulo}}</h2>
        </div>
        <div class="content">
            <p>Olá,</p>
            <p>{{mensagem}}</p>
            <div style="margin-top: 20px;">
                {{detalhes}}
            </div>
            <a href="{{url}}" class="btn">Ver Detalhes</a>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático do sistema. Por favor, não responda.</p>
        </div>
    </div>
</body>
</html>'
            ],
            'reclamante_editar' => [
                'assunto' => 'Reclamante Atualizado - {{titulo}}',
                'corpo' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #6c757d; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; margin-top: 20px; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{titulo}}</h2>
        </div>
        <div class="content">
            <p>Olá,</p>
            <p>{{mensagem}}</p>
            <div style="margin-top: 20px;">
                {{detalhes}}
            </div>
            <a href="{{url}}" class="btn">Ver Detalhes</a>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático do sistema. Por favor, não responda.</p>
        </div>
    </div>
</body>
</html>'
            ],
        ];
    }
}
