CARD #7: Aprovar/Rejeitar Payment Request (Update)
Labels: core, critical, api

Descrição:
- PATCH /api/payment-requests/{id}/approve
- PATCH /api/payment-requests/{id}/reject
- Apenas usuários com role=finance podem executar.
- Apenas status=pending pode ser aprovado/rejeitado.
- Registrar approved_by e approved_at.
- Retornar erro 403 se não for finance, 400 se não estiver pending.

Critérios de Aceitação:
- [ ] Finance aprova/rejeita com sucesso.
- [ ] Employee recebe 403. 
- [ ] Request já aprovado/rejeitado/expirado retorna 400.
- [ ] Testes unitários para todas as combinações de permissão.

Estimativa: 3h
Branch: feature/day3-rules-jobs
---------------------------------------------------------------------------
CARD #8: Scheduled Task — Expiração Automática (48h)
Labels: automation, critical, scheduled-task

Descrição:
- Command `payment-requests:expire`.
- Atualiza status=pending → expired onde created_at > 48h.
- Registrar expired_at no registro.
- Agendar no Kernel: `$schedule->command(...)->hourly();`
- Testar manualmente com `php artisan schedule:run`.

Critérios de Aceitação:
- [ ] Command executa e expira requests corretamente.
- [ ] Apenas pending são afetados.
- [ ] Teste unitário simulando passagem de tempo (Carbon::setTestNow).
- [ ] Documentado no README como verificar/agendar.

Estimativa: 3h
Branch: feature/day3-rules-jobs
---------------------------------------------------------------------------
CARD #9: Validação & Error Handling
Labels: quality, api
 
Descrição:
- Form Requests para todos os endpoints (StorePaymentRequest, etc.).
- Validação de currency (ISO 4217, 3 letras).
- Validação de amount (numeric, > 0).
- Validação de descrição (string, max 500).
- Global exception handler retornando JSON padronizado:
{ "error": true, "message": "...", "code": 422, "details": {} }
- Logs de erro em storage/logs.

Critérios de Aceitação:
- [ ] Todas as requests validam entrada.
- [ ] Mensagens de erro claras e em inglês (padrão API).
- [ ] Nenhum stack trace exposto em produção.
- [ ] Testes para cenários de validação inválida.

Estimativa: 3h
Branch: feature/day4-tests-polish
---------------------------------------------------------------------------
CARD #10: Unit Tests — Cobertura Crítica
Labels: testing, critical

Descrição:
- AuthTest: register, login, logout, token expiration.
- PaymentRequestTest: create (com mock de exchange rate), list, show.
- ApprovalTest: approve/reject por finance, tentativa por employee.
- ExpirationTest: command de expiração após 48h.
- ExchangeRateTest: service com mock HTTP, cache, fallback.
- Usar SQLite in-memory para velocidade.
- Meta: >80% cobertura nas classes críticas.

Critérios de Aceitação:
- [ ] `php artisan test` passa 100%.
- [ ] Mínimo 15 testes cobrindo fluxos principais.
- [ ] Mock de API externa em testes (não bater na API real).
- [ ] Relatório de cobertura gerado (opcional).

Estimativa: 5h
Branch: feature/day4-tests-polish
---------------------------------------------------------------------------
CARD #11: Documentação de API (Postman/README)
Labels: docs, critical

Descrição:
- README.md obrigatório (sem isso, teste é ignorado).
- Seção: Setup (clone, composer, env, migrate, seed, passport).
- Seção: Endpoints — tabela com Método, URL, Auth, Params, Descrição.
- Exemplos de request/response em JSON para cada endpoint.
- Collection Postman exportada (opcional mas altamente recomendado).
- Instruções para rodar testes e scheduled task.

Critérios de Aceitação:
- [ ] README permite setup do zero em < 10 minutos.
- [ ] Todos os endpoints documentados com exemplos reais.
- [ ] Variáveis de ambiente explicadas (.env.example).
- [ ] Collection Postman anexada (bonus).

Estimativa: 4h
Branch: feature/day5-docs-readme
---------------------------------------------------------------------------
CARD #12: Vídeo/URL de Demonstração
Labels: delivery, critical

Descrição:
- Gravar vídeo (3-5 min) mostrando:
- Setup do projeto.
- Login de employee e criação de payment request.
- Login de finance e aprovação.
- Listagem com filtros.
- Execução do comando de expiração.
- Rodada de testes (`php artisan test`).
- Ou: fazer deploy em plataforma gratuita (Railway, Render, Heroku).
- Compartilhar link público.
 
Critérios de Aceitação: 
- [ ] Vídeo claro e objetivo (ou URL funcional).
- [ ] Todos os requisitos do teste demonstrados.
- [ ] Link público acessível.
 
Estimativa: 3h
Branch: feature/day5-docs-readme (assets no repo ou link no README)
---------------------------------------------------------------------------
CARD #13: Revisão Final & Merge para Main 
Labels: delivery, critical
 
Descrição: 
- Revisar todos os PRs, garantir que develop está estável.
- Rodar testes finais, lint (pint), análise estática (opcional). 
- Merge develop → main. 
- Tag `v1.0.0` no commit final.
- Verificar README, .env.example, e link do vídeo.
 
Critérios de Aceitação: 
- [ ] `main` contém código final testado.
- [ ] Tag v1.0.0 criada.
- [ ] README validado por leitura completa. 
- [ ] Formulário de submissão preenchido com repo + vídeo. 
 
Estimativa: 2h
Branch: main (merge de develop)
