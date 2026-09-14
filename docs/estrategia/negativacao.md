# Auditoria de Termos de Pesquisa e Negativação Inteligente

**Puxe termos de pesquisa da sua campanha → Faça uma busca no Google (SERP) para validar a intenção de compra → Negative termos ruins de forma defensiva.**

Este processo (SOP) serve para limpar termos irrelevantes que estão consumindo sua verba no Google Ads. Em vez de simplesmente negativar qualquer termo sem conversão (o que pode excluir termos de alta intenção com baixo volume), nós usamos regras de corte estatístico associadas a uma checagem rápida de intenção de busca no próprio buscador do Google.

---

## Como Funciona o Processo

1. **Extração de Dados:** O script coleta todos os termos de pesquisa que dispararam seus anúncios nos últimos N dias.
2. **Filtro Estatístico:** Filtra os termos que possuem impressões suficientes para serem válidos (ex: ≥100 impressões) ou que gastaram mais do que o esperado sem gerar nenhuma venda.
3. **Checagem de Intenção (SERP Check):** Para cada termo candidato a descarte, o assistente faz uma pesquisa orgânica no Google (simulando a busca do usuário) e avalia o que aparece na primeira página de resultados.
4. **Decisão Qualitativa:** O assistente classifica os termos em `BAD` (ruim - sem intenção de compra) ou `KEEP` (manter - tem intenção, mas ainda não converteu).
5. **Aprovação do Usuário:** O assistente exibe a tabela estruturada para você revisar e escolher quais termos adicionar como negativas.
6. **Mutate na API:** Os termos aprovados são adicionados como correspondência de frase (phrase match) no nível da campanha.

---

## Critérios de Julgamento da SERP (Para Venda de Cursos)

Quando o Claude analisa os primeiros resultados de pesquisa orgânica para um termo candidato, ele aplica estes critérios de classificação:

| Sinais da primeira página (SERP) | Classificação | Ação Recomendada |
|---|---|---|
| Resultados são vagas de emprego (LinkedIn, Glassdoor, Indeed) | ❌ **BAD** | Adicionar como negativa (Intenção de carreira/trabalho, não de estudo) |
| Resultados são downloads piratas, torrents ou links do Reclame Aqui | ❌ **BAD** | Adicionar como negativa |
| Resultados focam em tutoriais gratuitos rápidos ("como fazer X em 5 min") | ❌ **BAD** | Adicionar como negativa (Intenção informativa, não de compra de curso longo) |
| Resultados mostram páginas de vendas de cursos concorrentes | ✅ **KEEP** | Manter (Intenção de compra clara, o anúncio está no lugar certo) |
| Resultados são mistos ou incertos | ⚠ **UNCERTAIN** | Apresentar para avaliação manual do usuário |

---

## Perguntas para a Execução

O assistente confirmará estas variáveis antes de rodar o relatório:

1. **Campanha:**
   > *"Qual campanha você deseja auditar? (Digite o ID ou 'todas')"*

2. **Período de Dados:**
   > *"Quantos dias de dados deseja puxar? (Padrão: 30 dias)"*

3. **Corte de Impressões:**
   > *"Mínimo de impressões para análise? (Padrão: 100)"*

4. **Corte de Custo Wasted (Verba gasta sem conversão):**
   > *"Mínimo de valor gasto em reais por termo para considerarmos análise? (Padrão: R$ 20,00)"*

---

## Exemplo de Apresentação de Resultados

O assistente imprimirá uma tabela como esta para sua validação:

```
RANK · GASTO   · TERMO DE BUSCA             · VEREDITO DA SERP (INTENÇÃO)
1    · R$75,20 · "vagas de programador web"  · ❌ BAD - Vagas de emprego dominam a SERP
2    · R$25,30 · "curso javascript gratis"  · ❌ BAD - Foco em conteúdo 100% gratuito
3    · R$22,40 · "aprender javascript do 0" · ✅ KEEP - Intenção de estudo/curso clara
4    · R$19,10 · "como instalar node js"    · ❌ BAD - Tutorial informativo rápido
```

> *"Quais termos deseja negativar como frase? Responda com os números do rank (ex: '1, 2, 4') ou 'todos os ruins'."*

---

*Última validação: 2026-06-12.*
