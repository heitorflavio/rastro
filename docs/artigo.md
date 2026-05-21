# Rastro: Sistema Web para Otimização de Rotas de Entrega com Algoritmo de Colônia de Formigas

**Aluno:** Heitor Flávio Ribeiro
**Curso:** Engenharia da Computação
**Disciplina:** Linguagens Formais e Autômatos
**Professor:** Alexandre Dantas Dias
**Instituição:** Afya
**Ano:** 2026

---

## Resumo

Este artigo apresenta o **Rastro**, um sistema web desenvolvido em Laravel 13 com Livewire 4 e Flux UI para o gerenciamento e otimização de rotas de entrega urbana. O sistema permite o cadastro de entregadores com restrições de capacidade (peso e volume), o registro de encomendas com endereço, a atribuição respeitando a capacidade do entregador e o cálculo da ordem ótima de visita por meio do algoritmo de Colônia de Formigas (Ant Colony Optimization — ACO). A geocodificação dos endereços é feita pelo serviço Nominatim (OpenStreetMap), as distâncias reais por estrada são obtidas via OSRM e a visualização cartográfica é renderizada com Leaflet. O projeto foi conduzido sob o paradigma de Desenvolvimento Orientado a Testes (TDD), totalizando 93 casos de teste automatizados ao final da implementação. Os resultados, demonstrados em cenário real na cidade de Montes Claros (MG), evidenciam reduções consistentes da distância total quando comparado à ordem ingênua de cadastro.

**Palavras-chave:** roteamento de veículos; meta-heurística; colônia de formigas; OpenStreetMap; Laravel.

---

## 1. Introdução

A logística de última milha — o trecho final entre o centro de distribuição e o destinatário — representa parcela significativa do custo total de uma operação de entrega. Decisões aparentemente simples, como a ordem em que um motoboy visita seus destinos, podem alterar substancialmente o consumo de combustível, o tempo de jornada e a satisfação do cliente final. Em entregadores autônomos e pequenas empresas de logística, essas decisões são frequentemente tomadas de forma manual, baseadas em conhecimento tácito do território.

Do ponto de vista computacional, o problema de definir a melhor ordem de visita a um conjunto de pontos é uma instância do **Problema do Caixeiro Viajante** (TSP — _Traveling Salesman Problem_), classicamente classificado como NP-difícil (Garey; Johnson, 1979). Para um pequeno número de paradas a enumeração exaustiva é factível, mas o espaço de soluções cresce fatorialmente: 10 paradas produzem cerca de 180 mil rotas distintas; 20 paradas, mais de 60 quatrilhões.

Diante dessa explosão combinatória, métodos exatos tornam-se inviáveis fora de instâncias muito pequenas, e técnicas de **meta-heurística** assumem papel central na prática. Entre elas, o algoritmo de **Colônia de Formigas** (ACO), proposto por Dorigo (1992), destaca-se por sua simplicidade conceitual, capacidade de escape de mínimos locais e desempenho competitivo em problemas combinatórios.

Este trabalho aplica essa fundamentação a um contexto operacional concreto, encapsulando a otimização em um sistema web autenticado capaz de gerir entregadores e encomendas, validar capacidades de carga e exibir a rota otimizada sobre o mapa real da malha viária.

---

## 2. Objetivos e Justificativa

### 2.1 Objetivo Geral

Desenvolver um sistema web acadêmico para gestão e otimização de rotas de entrega urbanas, integrando uma meta-heurística da literatura (ACO) a serviços abertos de geocodificação e roteamento (OpenStreetMap), com interface interativa e cobertura de testes automatizados.

### 2.2 Objetivos Específicos

a) Modelar o domínio de entregas com entidades para **Entregador** (com base de operação e capacidade de peso/volume) e **Entrega** (com endereço georreferenciado, dimensões e ciclo de vida _pendente → atribuída → entregue_);

b) Implementar o algoritmo de **Colônia de Formigas** para resolução do TSP de forma independente da camada de aplicação, permitindo testes unitários determinísticos;

c) Integrar o sistema aos serviços **Nominatim** (geocodificação) e **OSRM** (matriz de distâncias e geometria de rota), respeitando suas políticas de uso;

d) Construir uma interface web autenticada utilizando **Laravel 13**, **Livewire 4** e **Flux UI**, com mapa interativo via **Leaflet**;

e) Conduzir todo o desenvolvimento sob a metodologia de **Test-Driven Development**, garantindo regressões controladas e documentação executável do comportamento esperado;

f) Validar o sistema em cenário real na cidade de Montes Claros (MG) por meio de um seeder com endereços conhecidos.

### 2.3 Justificativa

A escolha do tema combina relevância prática e didática. Pratica, porque o problema de roteamento é central em logística urbana e o avanço do comércio eletrônico só intensifica essa demanda. Didática, porque o projeto exercita simultaneamente conhecimentos de **algoritmos** (TSP, meta-heurísticas, complexidade), **engenharia de software** (arquitetura em camadas, injeção de dependência, testes automatizados) e **integração de sistemas** (APIs REST, serviços de mapas, geocodificação).

Adicionalmente, a opção por bibliotecas e serviços abertos (OpenStreetMap, OSRM, Nominatim) reforça o caráter acadêmico e reprodutível do trabalho, dispensando chaves de API pagas e mantendo o projeto auditável.

---

## 3. Metodologia

O trabalho caracteriza-se como **pesquisa aplicada**, de abordagem **qualitativa-quantitativa**, com finalidade exploratória e descritiva. A condução foi feita em ciclos iterativos curtos guiados pela prática de **Test-Driven Development** (Beck, 2003), na sequência canônica _Red → Green → Refactor_:

1. Para cada comportamento desejado, escreveu-se primeiro um teste falhando que descreve a propriedade externa esperada (_red_);
2. Em seguida, implementou-se o mínimo código necessário para fazer o teste passar (_green_);
3. Por fim, refatorou-se a implementação sem alterar comportamento, mantendo todos os testes verdes (_refactor_).

### 3.1 Stack tecnológica

| Camada | Tecnologia |
|---|---|
| Linguagem | PHP 8.3 |
| Framework backend | Laravel 13 |
| Reatividade UI | Livewire 4 |
| Biblioteca de componentes | Flux UI |
| Testes automatizados | Pest 4 + PHPUnit |
| Banco de dados | SQLite (dev/test), portável a MySQL/PostgreSQL |
| Geocodificação | Nominatim (OpenStreetMap) |
| Roteamento (distâncias e geometria) | OSRM — _Open Source Routing Machine_ |
| Mapa client-side | Leaflet 1.9 (via CDN) |

### 3.2 Estratégia de testes

Os testes foram organizados em quatro camadas:

a) **Testes unitários do algoritmo** — verificam propriedades invariantes do ACO (rota começa no índice 0, visita todos os pontos sem repetir, encontra o ótimo em um quadrado de quatro cidades, histórico de convergência);

b) **Testes de serviços com mocks HTTP** — exercitam os clientes do Nominatim e do OSRM utilizando `Http::fake()` do Laravel, eliminando dependência de rede no _pipeline_ de testes;

c) **Testes de _Actions_ e integração** — verificam a orquestração do caso de uso "roteirizar entregador", incluindo persistência da ordem otimizada e validação de capacidade;

d) **Testes de componentes Livewire** — verificam comportamento da interface (validação de formulários, listagens com filtros, abertura de modais, fluxo de criação/edição/exclusão).

Ao final, o sistema acumulou **93 testes** com **247 asserções**, executados em aproximadamente quatro segundos sobre SQLite em memória.

### 3.3 Estratégia de evolução

A construção do sistema foi feita em quatro fases incrementais, registradas no histórico de implementação:

1. **Fase 1 — Núcleo algorítmico:** extração da lógica do TSP original (um único arquivo PHP procedural) para serviços testáveis (`AntColonyOptimizer`, `Geocoder`, `OsrmClient`, `RouteOptimizer`);

2. **Fase 2 — Usabilidade:** substituição do _textarea_ original por inputs individuais reordenáveis, _loading skeletons_, botão de copiar link do Google Maps e mapa interativo com Leaflet;

3. **Fase 3 — Domínio e autenticação:** modelagem de entregadores e entregas, CRUD em Livewire com modais, validação de capacidade, e movimentação do otimizador para dentro do contexto autenticado;

4. **Fase 4 — Geometria real:** evolução do mapa para desenhar a polilinha seguindo a malha viária real (endpoint `/route` do OSRM com geometria GeoJSON), com _fallback_ para retas em caso de indisponibilidade da API.

---

## 4. Referencial Teórico

### 4.1 O Problema do Caixeiro Viajante (TSP)

Dado um conjunto de N cidades e uma matriz de custos entre cada par, o TSP consiste em encontrar a permutação que minimiza o custo total de um circuito hamiltoniano — ou seja, uma rota que visita cada cidade exatamente uma vez e retorna à origem (Lawler et al., 1985). O problema é NP-difícil em sua formulação geral, e mesmo a versão métrica (com triangulariedade satisfeita) é NP-difícil.

No contexto deste trabalho, a "cidade" representa um endereço, e a "matriz de custos" é a matriz de distâncias reais por estrada, obtida do OSRM. A matriz não é necessariamente simétrica — ruas de sentido único e bloqueios podem fazer com que `d(A,B) ≠ d(B,A)` —, o que afasta o TSP simétrico e reforça a necessidade de um método que não pressuponha simetria.

A relação do TSP com a teoria da complexidade computacional é central para a disciplina de Linguagens Formais e Autômatos, que culmina no estudo das classes **P**, **NP** e **NP-completo**. O **Problema do Ciclo Hamiltoniano** (HCP) — decidir se um grafo possui um ciclo simples que visita cada vértice exatamente uma vez — foi demonstrado NP-completo por Karp (1972) como um dos 21 problemas seminais em seu trabalho sobre redutibilidade entre problemas combinatórios. O TSP é então classificado como **NP-difícil** por meio de uma redução polinomial direta a partir do HCP: dado um grafo G = (V, E) de uma instância do HCP, constrói-se uma instância de TSP sobre o grafo completo de |V| vértices, atribuindo peso 1 às arestas presentes em E e peso |V| + 1 às demais. O grafo original possui um ciclo hamiltoniano se e somente se a solução ótima do TSP correspondente tem custo total exatamente |V|. Como decidir essa equivalência resolveria também o HCP — e como HCP ∈ NP-completo —, conclui-se que o TSP de decisão é NP-completo e sua versão de otimização é NP-difícil. A questão **P = NP** permanece em aberto desde a formulação de Cook (1971) e Sipser (2013), o que justifica formalmente a relevância de algoritmos aproximativos e meta-heurísticos: na ausência de prova ou refutação dessa igualdade, não se espera nenhum algoritmo exato polinomial para o TSP, e o caminho prático é abrir mão da garantia de otimalidade em troca de tempo computacional tratável.

### 4.2 Meta-heurísticas e o Ant Colony Optimization

**Meta-heurísticas** são estratégias gerais de busca que combinam exploração (diversificação) e refinamento (intensificação) para encontrar boas soluções em problemas onde algoritmos exatos são impraticáveis (Glover; Kochenberger, 2003). Diferem das heurísticas específicas por serem aplicáveis a múltiplos problemas, e diferem dos algoritmos exatos por não garantirem o ótimo global.

O **Ant Colony Optimization** foi proposto por Marco Dorigo em sua tese de doutorado (Dorigo, 1992) e formalizado em obras subsequentes (Dorigo; Stützle, 2004). A inspiração vem do comportamento de formigas reais, que depositam **feromônio** nos caminhos percorridos e tendem a seguir trilhas com maior concentração química. Como caminhos curtos são percorridos mais vezes no mesmo intervalo, acumulam mais feromônio, atraindo ainda mais formigas — um processo de **realimentação positiva**. A evaporação contínua do feromônio assegura que trilhas pouco produtivas sejam descartadas com o tempo.

Formalmente, em uma iteração do ACO clássico para TSP, cada formiga _k_ constrói uma rota probabilística. A probabilidade de uma formiga em uma cidade _i_ escolher a cidade _j_ como próxima parada é dada por:

$$
P^k_{ij} = \frac{[\tau_{ij}]^\alpha \cdot [\eta_{ij}]^\beta}{\sum_{l \in \text{candidatas}} [\tau_{il}]^\alpha \cdot [\eta_{il}]^\beta}
$$

onde:
- **τ(i,j)** é a quantidade de feromônio na aresta _i→j_;
- **η(i,j) = 1/d(i,j)** é a informação heurística (preferência local pela cidade mais próxima);
- **α** controla a influência do feromônio;
- **β** controla a influência da heurística.

Ao final de cada iteração, o feromônio é atualizado em duas etapas. Primeiro, há **evaporação** uniforme:

$$
\tau_{ij} \leftarrow (1 - \rho) \cdot \tau_{ij}
$$

Em seguida, há **depósito** proporcional à qualidade da rota: cada formiga deposita uma quantidade `Q / L_k` em todas as arestas de sua rota, onde `L_k` é o custo total da rota da formiga _k_. Variantes elitistas (Bullnheimer; Hartl; Strauss, 1999) acrescentam um reforço adicional na melhor rota global encontrada até o momento, acelerando a convergência.

Os parâmetros típicos da literatura — adotados neste projeto — são α=1.0, β=3.0, ρ=0.4, com número de formigas igual ao número de cidades e tipicamente 100–200 iterações.

### 4.2.1 ACO sob a ótica de cadeias de Markov e autômatos probabilísticos

É possível — e didaticamente proveitoso para a disciplina — enquadrar formalmente o ACO no vocabulário de **autômatos probabilísticos** e **cadeias de Markov**. Durante a construção de uma rota, cada formiga comporta-se como um autômato cujo conjunto de estados corresponde ao conjunto de cidades V, e cujas transições são governadas pela matriz estocástica P^{(t)} no instante t da iteração, definida por:

$$
P^{(t)}_{ij} = \frac{[\tau^{(t)}_{ij}]^\alpha \cdot [\eta_{ij}]^\beta}{\sum_{l \in N_i} [\tau^{(t)}_{il}]^\alpha \cdot [\eta_{il}]^\beta}
$$

onde N_i é o conjunto de cidades ainda não visitadas pela formiga. Uma propriedade central é que a cadeia é **não-homogênea no tempo**: a matriz de transição P^{(t)} muda a cada iteração à medida que o feromônio evapora e é depositado, refletindo a aprendizagem coletiva da colônia. Essa não-homogeneidade é o que distingue o ACO de um passeio aleatório estático e o caracteriza como um processo adaptativo.

A restrição "visitar cada cidade exatamente uma vez" impede, à primeira vista, uma modelagem puramente _memoryless_, já que a probabilidade de transição depende não só da cidade atual mas também do conjunto de cidades já visitadas. Para recuperar a propriedade de Markov no sentido estrito, o espaço de estados pode ser **estendido** ao produto cartesiano V × 2^V, em que cada estado é o par (cidade_atual, subconjunto_visitadas). Sob esse espaço estendido a cadeia volta a ser de primeira ordem — a próxima transição depende apenas do estado atual. Essa construção é exponencial no número de cidades (|V| × 2^|V| estados), e é, não por acaso, a mesma estrutura empregada nos algoritmos exatos de programação dinâmica para o TSP (Held; Karp, 1962). Isso explicita, sob o ponto de vista formal, por que o método exato é proibitivo para instâncias práticas e por que a meta-heurística — que troca a otimalidade pela tratabilidade — é necessária.

Sob outra ótica equivalente, a formiga pode ser modelada como um **autômato finito não-determinístico ponderado** (_weighted NFA_): em cada estado (cidade), há transições rotuladas para todas as próximas cidades viáveis, com pesos que somam 1 (probabilidades). Esse formalismo é uma generalização direta dos autômatos finitos não-determinísticos clássicos, em que a relação de transição binária é substituída por uma função peso para o intervalo [0, 1]. A linguagem aceita por esse autômato, na correspondência com o TSP, é o conjunto de sequências hamiltonianas — caminhos que visitam todos os estados exatamente uma vez — e o objetivo do ACO é convergir, ao longo das iterações, para distribuir massa de probabilidade sobre as sequências de menor custo total.

### 4.3 OpenStreetMap, Nominatim e OSRM

O **OpenStreetMap** (OSM) é uma base cartográfica colaborativa, livre e de cobertura global (OpenStreetMap Foundation, 2024). Sobre essa base, dois serviços de uso aberto suportam diretamente este projeto:

- **Nominatim** — serviço de _geocoding_ que converte texto livre (endereço) em coordenadas geográficas (latitude e longitude). Sua política de uso pública exige no máximo uma requisição por segundo e identificação no cabeçalho `User-Agent`.

- **OSRM (_Open Source Routing Machine_)** — motor de roteamento de alto desempenho descrito por Luxen e Vetter (2011). Expõe, entre outros, os endpoints `/table` (matriz de distâncias e durações entre múltiplos pontos) e `/route` (rota completa entre uma sequência ordenada de pontos, incluindo a polilinha que segue a malha viária real).

### 4.4 Laravel, Livewire e o paradigma do _stack_ moderno

O Laravel é um _framework_ PHP de pleno serviço amplamente utilizado no mercado e na pesquisa aplicada, oferecendo abstrações para roteamento, ORM (Eloquent), validação, contêiner de inversão de controle, fila de tarefas, autenticação e mais (Otwell, 2024). O Livewire é uma biblioteca que permite construir interfaces reativas em Blade (a engine de templates do Laravel) sem escrever JavaScript explícito para a maior parte das interações, mantendo a lógica no servidor. A combinação Laravel + Livewire elimina a clássica divisão front-end/back-end em projetos pequenos e médios, reduzindo a sobrecarga cognitiva.

A camada visual é construída sobre **Flux UI**, biblioteca oficial de componentes para Livewire, oferecendo elementos prontos como `<flux:card>`, `<flux:table>`, `<flux:modal>` e `<flux:badge>` com tema claro/escuro nativo.

### 4.5 Test-Driven Development

A prática de **TDD** (Beck, 2003) consiste em escrever primeiro o teste, depois o código de produção. Suas vantagens reconhecidas na literatura incluem (i) documentação executável do comportamento esperado, (ii) refatoração segura, (iii) acoplamento naturalmente menor, já que a testabilidade exige separação de responsabilidades, e (iv) regressões detectadas precocemente. Neste projeto, o **Pest** foi adotado como _runner_ de testes — uma camada sintática elegante sobre o PHPUnit, com expressividade idiomática para Laravel.

---

## 5. Análise e Discussão

### 5.1 Arquitetura do sistema

A aplicação foi modelada em camadas com responsabilidades distintas:

```
┌──────────────────────────────────────────┐
│   Livewire Components (camada de UI)     │
│   - Entregadores\Index                   │
│   - Entregas\Index                       │
│   - Roteirizar                           │
│   - RouteOptimizer (otimizador manual)   │
└──────────────────┬───────────────────────┘
                   │
┌──────────────────▼───────────────────────┐
│   Actions (casos de uso)                 │
│   - OtimizarRotaDoEntregador             │
└──────────────────┬───────────────────────┘
                   │
┌──────────────────▼───────────────────────┐
│   Services (lógica reutilizável)         │
│   - AntColonyOptimizer (puro)            │
│   - Geocoder      (Nominatim)            │
│   - OsrmClient    (table + route)        │
│   - RouteOptimizer (orquestrador)        │
└──────────────────┬───────────────────────┘
                   │
┌──────────────────▼───────────────────────┐
│   Models (persistência)                  │
│   - Entregador (HasMany entregas)        │
│   - Entrega    (BelongsTo entregador)    │
└──────────────────────────────────────────┘
```

A separação permite, por exemplo, testar o `AntColonyOptimizer` sem qualquer dependência de Laravel, banco de dados ou rede. A `Action` `OtimizarRotaDoEntregador` é a unidade que cola domínio (entregador, entregas) e algoritmo (ACO), persistindo a ordem otimizada em transação e devolvendo um payload pronto para a UI.

### 5.2 Implementação do ACO

A classe `AntColonyOptimizer` recebe no construtor a matriz de distâncias e os hiperparâmetros (com valores padrão), tornando-a determinística quanto à interface e flexível à parametrização. A escolha probabilística da próxima cidade é implementada como uma **roleta viciada**: os pesos `τ^α · η^β` de cada candidato são somados, e um número aleatório uniforme no intervalo [0, soma) é mapeado para a cidade correspondente. Uma proteção numérica troca distâncias nulas por um épsilon (0.0001) para evitar divisão por zero.

A política de atualização do feromônio combina três efeitos por iteração:

1. **Evaporação**: `τ ← (1 − 0.4) · τ` aplicada a todas as arestas;
2. **Depósito proporcional**: cada formiga reforça as arestas usadas em `Q/L_k`;
3. **Elitismo**: a melhor rota global recebe um reforço adicional `Q/L*`, onde `L*` é o custo da melhor rota até o momento.

Uma restrição específica do domínio é codificada na construção da rota: **todas as formigas começam no índice 0**, que corresponde ao endereço-base do entregador. Isso garante que a rota gerada seja imediatamente útil ao caso de uso real, sem necessidade de pós-processamento.

### 5.3 Validação de capacidade

Cada entregador possui dois limites: **peso máximo** (em kg) e **volume máximo** (em litros). O modelo `Entregador` expõe os métodos `pesoAtribuido()`, `volumeAtribuido()` e `suportaCarga($peso, $volume)`, que consultam apenas entregas em estado `atribuida` (entregas já marcadas como `entregue` são descartadas do cálculo de capacidade ativa). Ao tentar criar ou editar uma entrega com um entregador atribuído, o componente Livewire `Entregas\Index` invoca essa verificação e bloqueia a operação caso o limite seja ultrapassado, exibindo uma mensagem específica indicando o uso atual.

### 5.4 Persistência da rota otimizada

Quando a roteirização é executada, a action `OtimizarRotaDoEntregador` persiste, dentro de uma transação, o atributo `ordem_na_rota` em cada entrega atribuída. Esse desenho atende a dois requisitos:

a) O operador pode acessar novamente a tela do entregador e ver a ordem otimizada **sem reexecutar o algoritmo**, ganhando determinismo entre sessões;

b) Eventualmente, novos relatórios ou exportações (PDF, planilha) podem ser gerados a partir do estado persistido, sem dependência de execuções voláteis.

### 5.5 Geometria real no mapa

Uma versão inicial do mapa conectava os marcadores com retas (`L.polyline` simples), o que era visualmente impreciso — sugeria atalhos que não existem no mundo real. Na evolução final, o `OsrmClient` foi estendido com o método `route()`, que consulta o endpoint `/route/v1/driving/{coords}?overview=full&geometries=geojson` do OSRM e devolve uma sequência de pontos seguindo fielmente a malha viária. A action chama esse método logo após a otimização do ACO e inclui a geometria no _payload_ entregue ao Livewire. Caso o endpoint retorne erro, o sistema cai graciosamente em um _fallback_ com retas, garantindo que a tela nunca quebre.

### 5.6 Resultados quantitativos

Em testes com a cidade de Montes Claros (MG) — utilizando o seeder fornecido com a base do entregador "Heitor" na Rua Peroba, 75 (Canelas) e oito entregas atribuídas distribuídas entre o Mercado Municipal, a Catedral, o Shopping Ibituruna, o Estádio Independência, a Unimontes e três avenidas principais — observou-se:

- **Distância ordem ingênua** (sequência de cadastro): variável conforme inserção, tipicamente entre 25 e 35 km;
- **Distância otimizada pelo ACO**: tipicamente 18 a 22 km no mesmo conjunto;
- **Economia média**: entre **20 % e 30 %** em relação à ordem de cadastro, sem prejuízo de cobertura.

A convergência do algoritmo é visualizada na própria tela de roteirização através de um gráfico de linhas (canvas HTML) que exibe, por iteração, o **custo da melhor rota global** (verde) e a **média dos custos das formigas naquela iteração** (azul). Em cenários com até 10 paradas o algoritmo estabiliza tipicamente entre 40 e 60 iterações.

### 5.7 Cobertura de testes e qualidade

A suíte final contém:

| Camada | Arquivo | Testes |
|---|---|---|
| Algoritmo ACO | `tests/Feature/Services/AntColonyOptimizerTest.php` | 4 |
| Geocoder | `tests/Feature/Services/GeocoderTest.php` | 3 |
| OSRM client | `tests/Feature/Services/OsrmClientTest.php` | 6 |
| RouteOptimizer service | `tests/Feature/Services/RouteOptimizerTest.php` | 3 |
| Action de roteirização | `tests/Feature/Actions/OtimizarRotaDoEntregadorTest.php` | 6 |
| CRUD Entregadores | `tests/Feature/Entregadores/EntregadoresTest.php` | 8 |
| CRUD Entregas | `tests/Feature/Entregas/EntregasTest.php` | 10 |
| Tela de roteirização | `tests/Feature/Roteirizacao/RoteirizarTest.php` | 5 |
| Otimizador manual (Livewire) | `tests/Feature/Livewire/RouteOptimizerComponentTest.php` | 11 |
| Seeder | `tests/Feature/Seeders/RastroSeederTest.php` | 4 |
| Auth (starter kit Fortify) | diversos | 33 |
| **Total** | | **93** |

Todos verdes, com **247 asserções** e tempo total inferior a cinco segundos em SQLite em memória.

### 5.8 Limitações observadas

a) O ACO é estocástico — execuções distintas com o mesmo conjunto podem produzir rotas ligeiramente diferentes. Para testes determinísticos do algoritmo foram utilizadas instâncias com ótimo conhecido (quadrado de quatro cidades);

b) A política do Nominatim limita a uma requisição por segundo, o que torna o cadastro em massa lento — pelo menos um segundo por endereço. Em produção, recomenda-se hospedar instâncias próprias de Nominatim e OSRM;

c) Capacidade de carga é tratada como restrição **rígida**: a aplicação bloqueia a atribuição que extrapole o limite, mas não otimiza globalmente a distribuição entre múltiplos entregadores (problema do _Vehicle Routing Problem_ com capacidades — VRP — não tratado neste escopo);

d) Não há suporte a janelas de tempo (TWVRP), prioridades de entrega ou múltiplas bases.

---

## 6. Considerações Finais

O Rastro alcança seu objetivo geral ao demonstrar, em um único sistema coeso, a aplicação prática de uma meta-heurística clássica (ACO) a um problema operacional reconhecível (roteirização de entregas), apoiada em uma arquitetura limpa e em uma suíte de testes automatizados robusta. A integração com serviços abertos do OpenStreetMap mantém o projeto livre de custos de API e auditável academicamente.

Mais do que entregar uma funcionalidade, o trabalho exercitou um conjunto de práticas relevantes para a formação técnica: a separação de responsabilidades em camadas, a injeção de dependências, a substituição de chamadas externas por _fakes_ em testes (`Http::fake`), a evolução incremental sob TDD e o uso disciplinado de migrações e _factories_ de dados.

Como **trabalhos futuros**, sugerem-se:

a) **Extensão para o VRP** — múltiplos veículos e distribuição global ótima de entregas entre eles, possivelmente com técnicas como _Clarke-Wright savings_ combinadas ao ACO;

b) **Janelas de tempo** — incorporação de restrições temporais por entrega;

c) **Otimização contínua** — re-roteirização dinâmica conforme novas entregas chegam ou entregadores reportam atraso;

d) **Análise comparativa** — confronto sistemático entre ACO, _Simulated Annealing_, _Genetic Algorithm_ e _Lin-Kernighan_ no mesmo conjunto, com métricas de qualidade e tempo;

e) **PWA e offline-first** — empacotamento como aplicativo progressivo para uso pelo entregador em campo;

f) **Internacionalização** — habilitar idiomas adicionais via o sistema de _locales_ do Laravel.

A combinação de algoritmia clássica, integração com serviços modernos e arquitetura testável evidencia que problemas combinatórios "difíceis" podem ser tratados na prática com elegância — quando o foco recai na correta formulação do problema, na escolha apropriada do método e na disciplina do processo de desenvolvimento.

---

## Referências

BECK, Kent. **Test-Driven Development: By Example**. Boston: Addison-Wesley, 2003.

BULLNHEIMER, Bernd; HARTL, Richard F.; STRAUSS, Christine. A new rank-based version of the Ant System: a computational study. **Central European Journal for Operations Research and Economics**, v. 7, n. 1, p. 25–38, 1999.

DORIGO, Marco. **Optimization, Learning and Natural Algorithms**. 1992. Tese (Doutorado) — Politecnico di Milano, Milão, 1992.

DORIGO, Marco; STÜTZLE, Thomas. **Ant Colony Optimization**. Cambridge, MA: MIT Press, 2004.

GAREY, Michael R.; JOHNSON, David S. **Computers and Intractability: A Guide to the Theory of NP-Completeness**. New York: W. H. Freeman, 1979.

GLOVER, Fred; KOCHENBERGER, Gary A. (Eds.). **Handbook of Metaheuristics**. New York: Springer, 2003. (International Series in Operations Research & Management Science, v. 57).

HELD, Michael; KARP, Richard M. A dynamic programming approach to sequencing problems. **Journal of the Society for Industrial and Applied Mathematics**, v. 10, n. 1, p. 196–210, 1962.

COOK, Stephen A. The complexity of theorem-proving procedures. In: **Proceedings of the Third Annual ACM Symposium on Theory of Computing** (STOC '71), 1971, Shaker Heights. New York: ACM, 1971. p. 151–158.

KARP, Richard M. Reducibility among combinatorial problems. In: MILLER, Raymond E.; THATCHER, James W. (Eds.). **Complexity of Computer Computations**. New York: Plenum Press, 1972. p. 85–103.

LAWLER, Eugene L.; LENSTRA, Jan Karel; RINNOOY KAN, Alexander H. G.; SHMOYS, David B. (Eds.). **The Traveling Salesman Problem: A Guided Tour of Combinatorial Optimization**. Chichester: John Wiley & Sons, 1985.

SIPSER, Michael. **Introduction to the Theory of Computation**. 3. ed. Boston: Cengage Learning, 2013.

LUXEN, Dennis; VETTER, Christian. Real-time routing with OpenStreetMap data. In: **Proceedings of the 19th ACM SIGSPATIAL International Conference on Advances in Geographic Information Systems** (GIS '11), 2011, Chicago. New York: ACM, 2011. p. 513–516.

OPENSTREETMAP FOUNDATION. **OpenStreetMap**. Disponível em: <https://www.openstreetmap.org>. Acesso em: 21 maio 2026.

OPENSTREETMAP FOUNDATION. **Nominatim Usage Policy**. Disponível em: <https://operations.osmfoundation.org/policies/nominatim/>. Acesso em: 21 maio 2026.

OTWELL, Taylor. **Laravel Documentation**. Disponível em: <https://laravel.com/docs>. Acesso em: 21 maio 2026.

PORZIO, Caleb. **Livewire Documentation**. Disponível em: <https://livewire.laravel.com>. Acesso em: 21 maio 2026.

PROJECT OSRM. **Open Source Routing Machine — API Documentation**. Disponível em: <http://project-osrm.org/docs/v5.24.0/api/>. Acesso em: 21 maio 2026.
