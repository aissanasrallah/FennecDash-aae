import { createHash } from 'node:crypto';

import type { FennecDashApi, MePayload, TokenRisk } from '../integrations/fennedash-api/client.js';

export interface Actor {
  readonly staffId: number;
  readonly email: string;
  readonly firstname: string;
  readonly lastname: string;
  readonly fullName: string;
  readonly admin: boolean;
  readonly roleId: number | null;
}

export interface TokenInfo {
  readonly id: number;
  readonly label: string;
  readonly prefix: string;
  readonly riskMax: TokenRisk;
  readonly scopes: readonly string[] | null;
}

export interface AuthIdentity {
  readonly actor: Actor;
  readonly token: TokenInfo;
  readonly tokenHash: string;
  readonly checkedAt: number;
}

export function hashToken(token: string): string {
  return createHash('sha256').update(token, 'utf8').digest('hex');
}

export function identityFromMe(me: MePayload, tokenHash: string): AuthIdentity {
  return {
    actor: {
      staffId: me.staff.id,
      email: me.staff.email,
      firstname: me.staff.firstname,
      lastname: me.staff.lastname,
      fullName: `${me.staff.firstname} ${me.staff.lastname}`.trim(),
      admin: me.staff.admin,
      roleId: me.staff.role_id,
    },
    token: {
      id: me.token.id,
      label: me.token.label,
      prefix: me.token.prefix,
      riskMax: me.token.risk_max,
      scopes: me.token.scopes,
    },
    tokenHash,
    checkedAt: Date.now(),
  };
}

interface CacheEntry {
  readonly identity: AuthIdentity;
  readonly expiresAt: number;
}

/**
 * Short-lived cache of successful `/me` validations. Revocation still takes
 * effect within MCP_AUTH_CACHE_TTL seconds; every tool call is authorized
 * again by the FennecDash API itself.
 */
export class AuthCache {
  private readonly entries = new Map<string, CacheEntry>();

  constructor(private readonly ttlSeconds: number) {}

  async resolve(client: FennecDashApi, token: string): Promise<AuthIdentity> {
    const tokenHash = hashToken(token);
    const now = Date.now();
    const cached = this.entries.get(tokenHash);

    if (cached && cached.expiresAt > now) {
      return cached.identity;
    }

    const me = await client.me();
    const identity = identityFromMe(me.data, tokenHash);

    if (this.ttlSeconds > 0) {
      this.entries.set(tokenHash, {
        identity,
        expiresAt: now + this.ttlSeconds * 1000,
      });
    }

    return identity;
  }

  invalidate(token: string): void {
    this.entries.delete(hashToken(token));
  }

  prune(): void {
    const now = Date.now();
    for (const [key, entry] of this.entries) {
      if (entry.expiresAt <= now) {
        this.entries.delete(key);
      }
    }
  }
}

interface AttemptWindow {
  count: number;
  resetAt: number;
}

/** Small in-memory guard against token guessing. */
export class AttemptLimiter {
  private readonly windows = new Map<string, AttemptWindow>();

  constructor(
    private readonly limit: number,
    private readonly windowMs: number,
  ) {}

  isBlocked(key: string): boolean {
    const now = Date.now();
    const window = this.windows.get(key);

    return window !== undefined && window.resetAt > now && window.count >= this.limit;
  }

  recordFailure(key: string): void {
    const now = Date.now();
    const window = this.windows.get(key);

    if (!window || window.resetAt <= now) {
      this.windows.set(key, { count: 1, resetAt: now + this.windowMs });
      return;
    }

    window.count += 1;
  }

  reset(key: string): void {
    this.windows.delete(key);
  }

  prune(): void {
    const now = Date.now();
    for (const [key, window] of this.windows) {
      if (window.resetAt <= now) {
        this.windows.delete(key);
      }
    }
  }
}
