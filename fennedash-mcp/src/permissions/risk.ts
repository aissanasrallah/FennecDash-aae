export const RISK_LEVELS = ['read', 'write', 'critical'] as const;

export type Risk = (typeof RISK_LEVELS)[number];

export const RISK_RANK: Record<Risk, number> = {
  read: 0,
  write: 1,
  critical: 2,
};

export function isRisk(value: unknown): value is Risk {
  return typeof value === 'string' && (RISK_LEVELS as readonly string[]).includes(value);
}

export function riskAtLeast(actual: Risk, required: Risk): boolean {
  return RISK_RANK[actual] >= RISK_RANK[required];
}
