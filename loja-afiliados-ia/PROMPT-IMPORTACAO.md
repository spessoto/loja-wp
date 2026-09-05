# Prompt para importar produto a partir de um print

Use este texto numa conversa com o Claude, anexando o(s) print(s) da página do produto (ex.: print da Amazon ou do Mercado Livre). O Claude lê a imagem e devolve o JSON pronto para colar em **Produtos → Importar com IA** no WordPress.

Se o print não mostrar tudo (por exemplo, cortou os comentários ou a ficha técnica), tire mais de um print da mesma página (role e capture o restante) e anexe todos juntos no mesmo pedido.

## Prompt

```
Você vai analisar o(s) print(s) em anexo de uma página de produto (loja de
afiliados). Extraia todas as informações visíveis e devolva **apenas** um
JSON válido, sem markdown, sem comentários, sem texto antes ou depois,
seguindo exatamente este formato:

{
  "titulo": "",
  "resumo_curto": "",
  "marca": "",
  "categoria": "",
  "sku": "",
  "link_afiliado": "COLOQUE_AQUI_O_LINK_DE_AFILIADO",
  "loja_destino": "Amazon | Mercado Livre | Outro",
  "badge": "",
  "preco_atual": 0.0,
  "preco_original": 0.0,
  "parcelamento": "",
  "avaliacao_nota": 0.0,
  "avaliacao_total": 0,
  "descricao": "",
  "bullets": [""],
  "destaques": [ { "valor": "", "label": "" } ],
  "especificacoes": [ { "chave": "", "valor": "" } ],
  "indicado_para": [""],
  "nao_indicado_para": [""],
  "avaliacoes": [
    { "nome": "", "local": "", "nota": 5, "data": "", "verificada": true, "texto": "" }
  ],
  "imagem_destaque": "",
  "imagens": [""]
}

Regras:
- "titulo": título completo do produto como aparece na página.
- "resumo_curto" e "marca": nome da marca/linha (ex.: "Vertax"). Se não
  identificar a marca, repita o início do título.
- "categoria": uma categoria curta e genérica (ex.: "Aspiradores",
  "Fones de ouvido", "Panelas").
- "sku": código/modelo do produto, se aparecer.
- "link_afiliado": não invente. Se eu não colar o link junto com o print,
  deixe o campo como "COLOQUE_AQUI_O_LINK_DE_AFILIADO" para eu preencher
  manualmente depois.
- "loja_destino": deduza pela marca d'água, cores ou layout do print
  (Amazon, Mercado Livre); se não der para saber, use "Outro".
- "preco_atual": preço final em número (ex.: 699.90), sem "R$" e sem
  separador de milhar.
- "preco_original": preço "de" riscado, se existir; senão 0.
- "parcelamento": texto como aparece (ex.: "10x de R$ 69,99 sem juros").
- "avaliacao_nota" e "avaliacao_total": nota média (0 a 5) e número de
  avaliações, se aparecerem.
- "descricao": um parágrafo (ou dois) resumindo a descrição/benefícios do
  produto que aparecem no print, em português, sem inventar características
  que não estão na imagem.
- "bullets": frases curtas de destaque que aparecem perto das fotos
  (ex.: "Indicado para pets", "Sem saco").
- "destaques": números/ícones de especificação rápida (ex.: potência,
  autonomia, peso) no formato { "valor": "450W", "label": "Potência de
  sucção" }.
- "especificacoes": a tabela de ficha técnica completa, uma linha por
  característica.
- "indicado_para" / "nao_indicado_para": listas de "para quem é" / "para
  quem não é", se existirem no print.
- "avaliacoes": comentários de clientes visíveis no print, com nome, local,
  nota, data e texto do comentário. Marque "verificada": true só se o print
  mostrar "compra verificada".
- "imagem_destaque" e "imagens": deixe como "" se eu não anexar as imagens
  do produto separadamente — vou preencher isso manualmente ou anexar as
  fotos do produto depois.
- Se algum campo não existir no print, use "" (texto), 0 (número) ou []
  (lista) — nunca invente dado que não está visível.

Aqui está(ão) o(s) print(s):
```

## Depois que o Claude devolver o JSON

1. Se o link de afiliado não foi preenchido automaticamente, edite o JSON e
   troque `"COLOQUE_AQUI_O_LINK_DE_AFILIADO"` pelo link real antes de
   importar.
2. Copie o JSON completo.
3. No WordPress, vá em **Produtos → Importar com IA**, cole no campo de
   texto e clique em **Importar produto**.
4. Como o print não gera URLs de imagem para download automático, adicione
   as fotos do produto manualmente depois, no meta box **Galeria de
   imagens** (ou na imagem destacada), usando a biblioteca de mídia.
