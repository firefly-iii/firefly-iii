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
*   Qual o local em que o Orçamento é vinculado a uma transação?
*   Onde o centro de custo poderá ser implementado no sistema?
*   Quais as dependências para serem criadas?
*   Como funciona o mecanismo de view/template?
*   Como utilizar o `artsan` para atualizar o banco de dados?

#### O que é o termo `journal`?

*   A exemplo do `Odoo`, um journal pode ser classificado como um `diário`. O Firefly III armazena cada transação financeira em "journals". Cada diário contém duas "transações". Um recebe dinheiro (-250 da sua conta bancária) e o outro o coloca em outra conta (+250 para a Amazon.com). https://docs.firefly-iii.org/en/latest/concepts/transactions.html

#### Quais locais o item `category` tem vínculo no model?

*   Tabelas:
    *   `categories`, `category_transaction` e `category_transaction_journal`.

#### Qual o local em que o Orçamento é vinculado a uma transação?

*   O orçamento é vinculado a uma transação-filha, pois ela pode ser dividida em cada item separadamente e não no valor total.

#### Onde o centro de custo poderá ser implementado no sistema?

*   Ele pode ser implementado semelhantemente a um orçamento, vinculado a uma transação-filha. 

#### Quais as dependências para serem criadas?

*   `/app/Repositories`: interface e repositório;
*   `/app/Providers`: Classe para atribuir instância para `$user`; 
*   `/app/Api/V1/Controllers/`: Controller, injetando o repositório;
*   `/app/Models/`: Modelo;
*   `/app/Import/Mapper/`: Mapeamento entre modelo e banco;
*   `/bootstrap/cache/services.php`: Registro no bootstrap (necessário?);
*   `/app/Http/Request/ReportFormRequest.php`: lista de categorias (para relatório?);

#### Como funciona o mecanismo de view/template?

/resources/views/V1 

#### Como utilizar o `artsan` para atualizar o banco de dados?

Exemplos a seguir:

```bash
php artisan make:migration create_users_table --create=users

php artisan make:migration add_votes_to_users_table --table=users
```

Mais na documentação em: https://laravel.com/docs/5.8/blade

#### O que é o tipo Carbon?

Uma extensão para Datetime, prove métodos para manipulação de datas

#### Como funciona o IoC?

Por meio da função `app(<<interface>>)`: 

```php
/** @var CategoryRepositoryInterface $repository */
$repository = app(CategoryRepositoryInterface::class);
```

A função está declarada no `helper.php` que chama o `Container->getInstance()->make()` para contruir uma nova instância. 

## Segunda etapa

*   Criar o CRUD de centros de custos;
*   Trocar o design pelo template do Prosocio;
*   Desenvolver suporte para multiusuários;
