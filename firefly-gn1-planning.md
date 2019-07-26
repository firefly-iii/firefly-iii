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

#### Quais as dependências para serem criadas?

*   `/app/Repositories`: interface e repositório;
*   `/app/Providers`: Classe para atribuir instância para `$user`; 
*   `/app/Api/V1/Controllers/`: Controller, injetando o repositório;
*   `/app/Models/`: Modelo;
*   `/app/Import/Mapper/`: popula um dicionário de dados pelo ID;
*   `/bootstrap/cache/services.php`: Registro no bootstrap (necessário?);
*   `/app/Http/Request/ReportFormRequest.php`: lista de categorias (para relatório listas em dropdown?);
*   `/app/Support/Http/Controllers/AugumentData.php`: Não está claro o uso;
*   `/app/Support/Http/Controllers/AutoCompleteCollector.php`: Autocomplete de campos;
*   `/app/Support/Http/Controllers/ChartGeneration.php`: Geração de gráfico; 
*   `/app/Support/Http/Controllers/PeriodOverview.php`: Relatório por período;
*   `/app/Support/Http/Controllers/RenderPartialViews.php`: renderiza views parciais;
*   `/app/Support/Import/Routine/File/MappedValuesValidator.php`: Cria objetos pelo Container para job de importação;
*   `/app/Support/Search/Search.php`: Resultado de busca;
*   `/app/Transformers`: Transforma o resultado do banco em array, recupera os campos do contexto;
*   `/tests/`: Todos os testes de unidade resultantes das mudanças realizadas no código;
*   `/public/v1/js/ff/common/autocomplete.js`: funções para definir onde obter a lista de itens para autocomplete; 
*   `/public/v1/js/ff/transacions/single/common.js`: chama a função de autocomplete para inicializar o componente; 

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

#### Onde está o `Helper` para elementos da view, como ExpandedForm?

A classe `ExpandedForm.php` fornecer métodos de helper para renderizar componentes, cujo HTML fica em `/resources/views/v1/form` .

## Segunda etapa

*   Criar o CRUD de centros de custos;
*   Trocar o design pelo template do Prosocio;
*   Desenvolver suporte para multiusuários;
