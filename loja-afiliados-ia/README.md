# Loja de Afiliados com IA

Plugin WordPress para montar uma vitrine de produtos de afiliado (Amazon, Mercado Livre, etc.), sem carrinho de compras e sem chave de API de IA. Você usa o seu plano pago do Claude, em uma conversa normal, para ler a página do produto e gerar os dados; o plugin importa esses dados e monta a página do produto automaticamente.

## O que o plugin faz

- Cadastro de "Produtos" (post type próprio, `produto_afiliado`) com preço, preço original/desconto, nota e número de avaliações, ficha técnica, bullets, "indicado para / não indicado para", avaliações de clientes e galeria de imagens.
- Botão único de ação: **Comprar agora**, que redireciona (`/ir/slug-do-produto/`) para o seu link de afiliado em nova aba, com `rel="nofollow sponsored noopener"` e contagem simples de cliques. O site nunca processa pagamento — quem compra, compra no site da Amazon/Mercado Livre.
- Comparador de produtos (`[lai_comparador]`, até 4 produtos, com base na ficha técnica).
- Lista de desejos (`[lai_wishlist]`), guardada no navegador do visitante (sem precisar de login).
- Recomendações automáticas ("Quem viu este, também comprou") por categoria/marca e faixa de preço.
- Vitrine da loja no arquivo padrão `/loja/` e shortcode `[lai_vitrine]` para usar em qualquer página.
- Layout da página de produto baseado no design enviado, **removendo**: informações de frete/garantia/troca, barra de "últimas unidades", box de "resumo da oferta" e box de "falar com especialista".

## Como importar um produto com a IA (sem gastar API)

1. Copie o **link de afiliado** do produto (Amazon, Mercado Livre, etc.).
2. Abra uma conversa com o Claude (app ou claude.ai, no seu plano pago) e peça, por exemplo:

   > Leia esta página de produto de afiliado e devolva os dados exatamente neste formato JSON, sem comentários, sem markdown: `<link>`

   e cole o modelo de JSON que aparece em **Produtos → Importar com IA** no admin do WordPress.
3. Cole o JSON que o Claude devolveu no campo da tela **Produtos → Importar com IA** e clique em **Importar produto**.
4. O plugin cria o produto, baixa as imagens para a biblioteca de mídia e preenche preço, ficha técnica, avaliações etc. Revise e ajuste manualmente o que quiser na tela normal de edição do produto.

### Automatizando com um agente (avançado)

Existe também o endpoint REST `POST /wp-json/loja-afiliados-ia/v1/importar`, que aceita o mesmo JSON no corpo da requisição (autenticado como usuário com permissão de editor). Isso permite que uma ferramenta de agente (ex.: MCP do WordPress) publique o produto diretamente, sem passar pela tela de admin.

## Páginas sugeridas

Crie páginas comuns do WordPress com os shortcodes:

- `[lai_comparador]` — em uma página `/comparador/`
- `[lai_wishlist]` — em uma página `/favoritos/`
- `[lai_vitrine]` — para destacar produtos em qualquer página (aceita os atributos `quantidade`, `categoria`, `marca`)

O arquivo padrão de produtos já fica disponível em `/loja/`.

## Estrutura de arquivos

```
loja-afiliados-ia.php              Bootstrap do plugin
includes/class-lai-cpt.php          Post type "produto_afiliado" e taxonomias (categoria, marca)
includes/class-lai-meta-boxes.php   Campos de edição manual do produto
includes/class-lai-importer.php     Lógica de importação a partir do JSON da IA
includes/class-lai-admin-import-page.php  Tela "Importar com IA"
includes/class-lai-rest-api.php     Endpoints REST (importar produto / buscar produtos por ID)
includes/class-lai-redirect.php     Redirecionamento /ir/slug/ para o link de afiliado
includes/class-lai-wishlist.php     Shortcode da lista de desejos
includes/class-lai-compare.php      Shortcode do comparador
includes/class-lai-recommendations.php  Regras de recomendação automática
includes/class-lai-shortcodes.php   Shortcode da vitrine + card de produto reutilizável
includes/class-lai-frontend.php     Assets, template da loja/produto, formatação de preço
templates/single-produto.php        Página do produto
templates/archive-produto.php       Vitrine/arquivo de produtos
assets/css/frontend.css             Estilos da loja
assets/js/frontend.js               Galeria, wishlist e comparador (localStorage + REST)
```
