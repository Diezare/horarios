<?php
require_once __DIR__ . '/../../configs/init.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Restrições da Geração</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 25px;
            color: #000;
        }

        h1 {
            font-size: 22px;
            margin-bottom: 20px;
        }

        .acoes {
            margin-bottom: 20px;
        }

        .acoes button {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 8px;
        }

        .btn-print {
            background: #6358F8;
            color: white;
        }

        .btn-close {
            background: #FC3B56;
            color: white;
        }

        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            font-family: Consolas, monospace;
            font-size: 13px;
            line-height: 1.6;
            background: #f7f7f7;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
        }

        @media print {
            .acoes {
                display: none;
            }

            body {
                margin: 12mm;
            }

            pre {
                border: none;
                background: white;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <h1>Relatório de Restrições da Geração Automática</h1>

    <div class="acoes">
        <button id="btn-print-relatorio" class="btn-print">Imprimir</button>
        <button id="btn-close-relatorio" class="btn-close">Fechar</button>
    </div>

    <pre id="conteudo-relatorio">Carregando relatório...</pre>

    <script src="<?php echo JS_PATH; ?>/relatorio-restricoes-geracao.js"></script>
</body>
</html>