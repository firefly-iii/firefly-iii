Planejamento de revisão do Firefly
==================================

## Primeira etapa

### Reproduzir categorias para centro de custo

*   Incluir o campo no cadastro de transação;
*   Incluir na seção "relatórios" o filtro por centro de custo;
*   Criar relatório de transações ao clicar sobre o centro de custo (show);

### Importação

*   Incluir o campo "centro de custo" no algoritmo de importação;

### Aparência

*   Incluir logomarca da GN1;
*   Alterar o título no template;
*   Retirar ajuda;

### Definições técnicas

*   Nome da nova tabela: `center_cost`

### Perguntas

*   O que a termo `journal`?
*   Quais locais o item `category` tem vínculo no model?
*   Quais as dependências para serem criadas?
*   Como funciona o mecanismo de view/template?
*   Como utilizar o `artsan` para atualizar o banco de dados?

## Segunda etapa

*   Criar o CRUD de centros de custos;
*   Trocar o design pelo template do Prosocio;
*   Desenvolver suporte para multiusuários;
