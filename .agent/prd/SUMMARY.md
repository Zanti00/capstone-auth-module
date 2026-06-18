# Executive Summary

This project overhauls the authentication and authorization architecture for the Smart Expense Reimbursement Management System (SERMS) and the Auth Module. The current bottleneck—caused by repeated network requests to validate opaque Sanctum tokens—will be completely eliminated.

**Core Vision**: We are moving to a stateless JWT authentication layer using Laravel Passport, combined with a high-performance Redis cache for authorization. 

**Key Features**:
1. **Stateless JWTs**: The Auth service will issue RS256 JWTs containing only "macro-context" (`user_id`, `role`, `department`). Downstream services will verify these locally using a shared public key.
2. **Redis Permission Cache**: Fine-grained permissions will be stored in a shared Redis cluster. Downstream services will fetch these in sub-milliseconds without hitting the Auth module's API or MySQL database.
3. **Instant Revocation**: A Redis-based blacklist will track revoked tokens (JTI), and permission updates will trigger immediate cache invalidation.
4. **Security-First**: Short token TTLs, strict data privacy in the token payload (no PII), and a "fail-closed" policy if Redis is unreachable.

By adopting this hybrid architecture, we expect per-request authorization overhead to drop from ~200ms to <2ms, resolving the severe UI latency currently experienced by end-users.
