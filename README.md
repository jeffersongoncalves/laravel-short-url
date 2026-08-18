# Laravel Short URL

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jeffersongoncalves/laravel-short-url.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-short-url)
[![Tests](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/laravel-short-url/tests.yml?branch=master&label=tests&style=flat-square)](https://github.com/jeffersongoncalves/laravel-short-url/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/jeffersongoncalves/laravel-short-url.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-short-url)

Motor headless de encurtamento de URLs para Laravel. Sem nenhuma dependência de Filament — consumido pelo pacote de UI [`jeffersongoncalves/filament-short-url`](https://github.com/jeffersongoncalves/filament-short-url), mas funciona sozinho em qualquer aplicação Laravel via Facade, API REST ou console.

## Por que este pacote

- **Alto throughput.** O pipeline de redirecionamento é uma cadeia de estágios independentes e testáveis (`Illuminate\Pipeline`), com cache do link resolvido e escrita de analytics assíncrona — nenhuma falha de integração externa (GeoIP, Safe Browsing, VPN, webhooks) derruba um redirect.
- **Orientado a contratos.** Toda peça substituível — driver de analytics, verificador de DNS, gerador de QR Code, checker de Safe Browsing, detector de VPN — é uma interface em `src/Contracts/`, com uma implementação padrão e registries extensíveis (`AnalyticsDriverRegistry`, `DeepLinkRegistry`, `PixelProviderRegistry`, `FilterTypeRegistry`, `ImporterDriverRegistry`).
- **Dependências mínimas.** Só `spatie/laravel-package-tools` é obrigatório. GeoIP (MaxMind), QR Code (`endroid/qr-code`), multi-tenancy (`stancl/tenancy`) e Redis (`predis/predis`) são todos opcionais — o pacote funciona perfeitamente sem eles, cada integração é guardada por `class_exists`/feature-flag.
- **Multi-idioma.** pt_BR, en e es prontos — nenhuma string hardcoded fora de `resources/lang`.

## Instalação

```bash
composer require jeffersongoncalves/laravel-short-url
```

Publique config, migrations e traduções:

```bash
php artisan vendor:publish --tag="short-url-config"
php artisan vendor:publish --tag="short-url-migrations"
php artisan vendor:publish --tag="short-url-translations"
php artisan migrate
```

## Uso rápido

```php
use JeffersonGoncalves\LaravelShortUrl\Facades\ShortUrl;

// Criar
$link = ShortUrl::create(['destination_url' => 'https://example.com/produto']);

// Fluente
$link = ShortUrl::destination('https://example.com/produto')
    ->key('promo25')
    ->expiresAt(now()->addDays(30))
    ->maxVisits(1000)
    ->password('segredo')
    ->create();

// Resolver
$link = ShortUrl::resolve('promo25');
```

O redirecionamento em si já funciona sem nenhuma linha de código extra: qualquer requisição em `GET /{urlKey}` passa pelo pipeline completo.

## O pipeline de redirecionamento

```
ResolveHost → RateLimit → ResolveShortUrl(cache) → DetectBot → DetectVpnProxy
→ CheckAvailability → RequirePassword → ShowWarning → ResolveDestination
→ BuildFinalUrl → RenderInterstitial → Respond → DispatchTracking
```

Cada estágio pode curto-circuitar retornando uma `Response` diretamente (senha incorreta, aviso de destino, link expirado, VPN bloqueada, limite de plano). O link resolvido fica em cache (`{host}:{key}`) e a invalidação acontece automaticamente em `saved`/`deleted`.

## Principais funcionalidades

| Área | Descrição |
| --- | --- |
| **Redirecionamento** | Base62 configurável, blacklist, unicidade por domínio, `301\|302\|307\|308`, `single_use`, `max_visits`, expiração com redirect de fallback. |
| **Analytics** | Visitas assíncronas (`TrackShortUrlVisitJob`), parser de UA com fast-path, GeoIP (headers CDN / MaxMind / ip-api), detecção de bot, anonimização de IP (IPv4 /24, IPv6 /48), agregação diária + retenção configurável. |
| **Segmentação** | Regras `and\|or` aninhadas por dispositivo, plataforma, navegador, país, idioma, referer, UTM, janela de data/hora, contagem de visitas, VPN, bot. Rotação A/B ponderada com significância estatística (teste Z). |
| **Domínios próprios** | Verificação DNS (TXT/CNAME/A), roteamento por domínio, wildcard, redirect de raiz. |
| **Segurança** | Senha com bcrypt, página de aviso com token assinado, Google Safe Browsing (bloqueio síncrono ou assíncrono), detecção de VPN/proxy (flag ou bloqueio 403), rate limiting, auditoria completa (before/after). |
| **Conformidade** | Retenção configurável, exportação/exclusão de dados por sujeito (LGPD), modo somente-analytics (sem PII). |
| **API REST** | `/api/short-url/v1` (desativada por padrão), autenticação por API key com abilities, rate limit por chave, CRUD de links, bulk (até 500), stats, visits, domínios, webhooks, conversões. |
| **Webhooks** | HMAC-SHA256 + timestamp anti-replay, retry 10s/60s/300s, replay manual, desativação automática após falhas consecutivas. |
| **Analytics externo** | GA4 Measurement Protocol e Plausible prontos; `AnalyticsDriverRegistry::extend()` para adicionar qualquer outro. |
| **Alertas** | Detecção de anomalia por z-score contra baseline de 7 dias, notificações por e-mail, banco, broadcast, Slack, Discord, Telegram, Teams. |
| **QR Code** | SVG/PNG/PDF/EPS (via `endroid/qr-code`, opcional), tracking de escaneamento (`?source=qr`). |
| **Deep links & pixels** | Abertura de app mobile por scheme customizado, 10 apps pré-cadastrados, AASA/assetlinks opcionais, pixels de retargeting (Meta, Google Ads, TikTok, GA4) com banner de consentimento opcional. |
| **Organização** | Pastas hierárquicas, tags, templates de UTM, arquivamento. |
| **Importação/Exportação** | CSV nativo, Bitly API v4 como referência de importador por provedor, exportação CSV via API. |
| **ClickHouse** | Driver alternativo de `VisitRepository` via HTTP nativo do ClickHouse — mesma interface, sem dependência de cliente. |
| **Multi-tenancy** | Feature-flag total. Escopo automático via `stancl/tenancy` (se instalado) ou config manual. Limites de plano (`links_per_month`, `domains`) configuráveis. |
| **Link-in-bio** | Páginas públicas em `/bio/{handle}` com blocos (link, texto, imagem, vídeo) e tracking de clique por bloco. |

## Configuração

Toda opção fica documentada inline em `config/short-url.php`. Os grupos principais:

`table_prefix`, `route`, `key`, `redirect`, `cache`, `tracking` (inclui `clickhouse`), `domains`, `branding`, `security` (senha, aviso, rate limit, VPN, safe browsing), `compliance`, `audit`, `api`, `webhooks`, `analytics`, `conversions`, `alerts`, `notifications`, `qr`, `deep_links`, `pixels`, `importers`, `tenancy`, `bio`.

Settings também podem ser lidas/gravadas em runtime via `Contracts\SettingsRepository`, com schema declarativo (`schema()`) para montar formulários dinâmicos no plugin de UI.

## Comandos artisan

Todos se auto-registram no agendador (`packageBooted()`), respeitando os toggles de config:

| Comando | Frequência |
| --- | --- |
| `short-url:sync-counters` | a cada minuto (com buffering de contadores ativo) |
| `short-url:aggregate-and-prune` | diário 02:00 |
| `short-url:verify-domains` | a cada 6h |
| `short-url:check-safe-browsing` | diário |
| `short-url:detect-anomalies` | horário |
| `short-url:send-scheduled-reports` | diário |
| `short-url:prune-webhook-deliveries` | semanal |
| `short-url:import {driver} {source}` | manual |

## Superfície pública (contrato com o plugin de UI)

```php
ShortUrl::create(array $attributes): ShortUrlModel
ShortUrl::destination(string $url): ShortUrlBuilder
ShortUrl::resolve(string $key, ?string $host = null): ?ShortUrlModel

// src/Contracts/
VisitRepository, GeoIpDriver, VpnDetectionDriver, AnalyticsDriver,
SafeBrowsingChecker, QrCodeBuilder, StatsAggregator, TargetingResolver,
DnsVerifier, SettingsRepository, WebhookDispatcher, ImporterDriver,
ConversionApiDispatcher

// src/Registries/
FilterTypeRegistry, AnalyticsDriverRegistry, DeepLinkRegistry,
PixelProviderRegistry, ImporterDriverRegistry
```

## Testes

```bash
composer test        # Pest
composer analyse      # PHPStan (Larastan) nível 5+
composer format        # Pint
```

O CI roda a suíte contra PHP 8.3/8.4 × Laravel 11/12 × PostgreSQL/MySQL/SQLite. Um teste de arquitetura (`Tests\Architecture\NoFilamentTest`) garante que nenhum arquivo importa `Filament\`.

## Segurança

Encontrou uma vulnerabilidade de segurança? Veja [SECURITY.md](.github/SECURITY.md).

## Créditos

- [Jefferson Gonçalves](https://github.com/jeffersongoncalves)
- [Todos os contribuidores](../../contributors)

## Licença

MIT. Veja [LICENSE.md](LICENSE.md) para mais informações.
