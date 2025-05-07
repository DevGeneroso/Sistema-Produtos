# Sistema de Gestão de Produtos

Um sistema web simples para cadastro e gestão de produtos, desenvolvido com PHP 8, JavaScript e Bootstrap 5.

## Funcionalidades

- Autenticação de usuários
- Registro de novos usuários
- Alteração de senha
- Cadastro de produtos
- Edição de produtos
- Exclusão de produtos (com regras de negócio)
- Listagem de produtos com paginação
- Filtros de busca por nome e status

## Requisitos

- PHP 8.0 ou superior
- MySQL 5.7 ou superior
- Servidor web (Apache, Nginx, etc.)

## Instalação

1. Clone ou baixe este repositório para o diretório do seu servidor web
2. Importe o arquivo `database.sql` para o seu servidor MySQL
3. Configure as credenciais do banco de dados no arquivo `config/database.php`
4. Acesse o sistema pelo navegador
   
## Credenciais de Acesso

- Usuário: admin
- Senha: sua_nova_senha

## Usuários

- O nome de usuário é obrigatório e único
- A senha deve ter pelo menos 6 caracteres
- Para alterar a senha, o usuário deve fornecer a senha atual correta
- As senhas são armazenadas de forma segura utilizando hash

## Regras de Negócio

- O código do produto é obrigatório e único
- O nome do produto é obrigatório
- A descrição é opcional
- O valor do produto, se não informado, será R$ 0,00
- Produtos com status 'ativo' não podem ser excluídos
- Produtos com quantidade maior que zero não podem ser excluídos

## Tecnologias Utilizadas

- PHP 8
- JavaScript
- Bootstrap 5
- MySQL
- PDO para conexão com o banco de dados
- Fetch API para requisições AJAX

## Funcionalidades de Usuário

### Registro de Usuários

O sistema permite que novos usuários se registrem através da página de registro. Durante o registro, o sistema verifica:

- Se o nome de usuário já existe
- Se a senha tem pelo menos 6 caracteres
- Se as senhas digitadas coincidem

### Alteração de Senha

Usuários autenticados podem alterar suas senhas através da página de alteração de senha. O sistema verifica:

- Se a senha atual está correta
- Se a nova senha tem pelo menos 6 caracteres
- Se as novas senhas digitadas coincidem

## Screenshots o Sistema

![Login](https://github.com/user-attachments/assets/a0632d3a-fe9c-4df4-90b8-1e3e54de6dde)

![Cadastro-Usuario](https://github.com/user-attachments/assets/9ee89318-09c9-4a4c-8b8b-becbdc6abfb6)

![Produtos](https://github.com/user-attachments/assets/1cbf118f-6085-405e-b583-112cfaeec587)

![Cadastrar-Produto](https://github.com/user-attachments/assets/10557a7e-e4ca-4576-82f9-c5a6d75bcb86)

![Editar-Produto](https://github.com/user-attachments/assets/d7ffadae-14b3-4496-9c53-33f113b73cb8)

![Excluir-Produto](https://github.com/user-attachments/assets/4003868f-0ebb-442e-aace-01fb5235afc9)

![Alterar-Senha](https://github.com/user-attachments/assets/3ec9bb78-e460-4718-b058-8f62662f6b65)
