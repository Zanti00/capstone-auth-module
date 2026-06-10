# PRD: Hybrid Authentication & Authorization Architecture

## 1. App Overview, Objectives and Success Criteria

**Objective**: Architect and implement a high-performance, stateless authentication and fine-grained authorization layer for the Sub-Systems.
**Problem**: The current system relies on Laravel Sanctum opaque tokens, requiring a network round-trip to the Auth service (`/api/internal/verify-token`) for every single downstream API request. This causes severe latency (5-10s for simple table loads).
**Success Criteria**:

- Sub-millisecond local token validation at the edge (in downstream microservices) using JWT public-key cryptography.
- Immediate (<50ms) permission enforcement via a shared Redis cache.
- Complete removal of Laravel Sanctum and opaque token validation logic.
- Total latency for authentication/authorization verification in Sub-systems drops to <2ms per request.

## 2. Target Audience

- Internal engineers and microservices (SERMS, CRMS, PRS, TS) that interact with the Auth module.
- End-users (employees, finance managers, etc) who require snappy, responsive interfaces without artificial latency.

## 3. Core Features and Functionality

- **TASK-1: Prerequisite Verification**: Verify Redis connectivity, Passport installation, and `.env` public/private key pairs.
- **TASK-2: Laravel Passport Integration**: Replace Sanctum with Passport. Issue RS256 JWTs upon successful login.
- **TASK-3: Custom JWT Claims (Macro-Context)**: Embed `user_id`, `role`, and `department` into the Passport JWT payload. Omit `tenant_id` for now.
- **TASK-4: Shared Redis Authorization Cache**: Store fine-grained permissions in a central Redis cluster keyed by `user_id`.
- **TASK-5: Cache Invalidation & Sync**: When roles or permissions change, immediately evict/update the Redis cache for affected users.
- **TASK-6: Token Revocation Blacklist**: Implement a Redis-based JWT blacklist to handle early logout or deactivation, keyed by the token's JTI.
- **TASK-7: Downstream Validation Library (Concept)**: Sub-systems will verify the JWT signature locally using the injected public key, then check Redis for the blacklist and fine-grained permissions.

## 4. Key User Flows and Journeys

- **Login Flow**: User submits credentials -> Auth service validates -> Auth service caches permissions in Redis -> Auth service generates RS256 JWT with macro-context -> Returns JWT to client.
- **API Request Flow (Downstream)**: Client calls Sub-systems API with JWT -> Sub-systems verifies JWT signature locally -> Sub-systems checks Redis blacklist -> Sub-systems fetches fine-grained permissions from Redis -> Sub-systems processes request.
- **Logout Flow**: User logs out -> Auth service adds JWT JTI to Redis blacklist with TTL matching token expiration -> Auth service flushes user's permission cache.
- **Admin Update Flow**: Admin changes user's role -> Auth service updates DB -> Auth service flushes/repopulates user's permission cache in Redis.

## 5. Technical Stack Recommendations

- **Authentication**: Laravel Passport (issuing RS256 JWTs).
- **Caching & State**: Redis (for permissions cache and token blacklist).
- **Cryptography**: RS256 (Public/Private key pair).

## 6. Prerequisites and Access

- **Database Access**: Verified. MySQL via docker-compose.
- **MCPs**: None required.
- **Environment Variables**: `PASSPORT_PRIVATE_KEY`, `PASSPORT_PUBLIC_KEY`, `JWT_PUBLIC_KEY`. Placeholders added to `auth-service/.env.local`.
- **Key Distribution**: Downstream services will receive the `oauth-public.key` via a shared Docker volume or base64-encoded environment variable (`JWT_PUBLIC_KEY`).

## 7. Conceptual Data Model

- **JWT Payload**:
  ```json
  {
    "sub": "user_123",
    "jti": "unique_token_id",
    "exp": 1718000000,
    "role": "Finance Manager",
    "department": "Finance"
  }
  ```
- **Redis Permission Cache**:
  - Key: `user_permissions:{user_id}`
  - Value: `["document:read", "billing:write", ...]`
- **Redis Blacklist**:
  - Key: `jwt_blacklist:{jti}`
  - Value: `true` (with TTL matching token expiration)

## 8. Security Considerations

- **Fail Closed Strategy**: If the shared Redis cluster goes down, macro-context operations (e.g., identity checking) can proceed, but any requests requiring fine-grained permissions MUST fail closed (HTTP 403/503).
- **Data Privacy**: JWTs must NOT contain PII (e.g., email, phone number). Only functional macro-context.
- **Short TTL**: JWTs will have a short expiration (e.g., 15-30 minutes) to minimize vulnerability windows.

## 9. Assumptions and Dependencies

- We are doing a "Big Bang" migration, removing Sanctum entirely. Users will be logged out and must re-authenticate.
- Downstream services (Sub-systems) are capable of connecting to the shared Redis cluster.
- The `auth-redis` container is accessible to all relevant microservices in the `shared-capstone-network`.
