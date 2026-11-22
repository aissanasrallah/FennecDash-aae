import assert from 'node:assert/strict';

import { describe, expect, it } from 'vitest';

import { buildRegistries } from '../../src/server/mcp-server.js';

describe('MCP registries', () => {
  const { tools, resources, prompts } = buildRegistries();
  const toolList = tools.list();

  it('registers a broad, non-trivial tool surface', () => {
    expect(toolList.length).toBeGreaterThanOrEqual(55);
    expect(resources.size).toBeGreaterThanOrEqual(10);
    expect(prompts.size).toBe(5);
  });

  it('follows the fennedash_<module>_<action> convention', () => {
    for (const tool of toolList) {
      if (tool.name === 'fennedash_discover') {
        continue;
      }

      expect(tool.name).toMatch(/^fennedash_[a-z0-9]+(?:_[a-z0-9]+)+$/);
      expect(tool.name).toBe(`fennedash_${tool.module}_${tool.action}`);
      expect(tool.title.trim().length).toBeGreaterThan(3);
      expect(tool.description.trim().length).toBeGreaterThan(30);
    }
  });

  it('has a discover tool and all three risk classes', () => {
    const names = new Set(toolList.map((tool) => tool.name));
    const risks = new Set(toolList.map((tool) => tool.risk));

    assert(names.has('fennedash_discover'));
    expect(risks).toEqual(new Set(['read', 'write', 'critical']));
  });

  it('only marks the documented critical write as critical', () => {
    const critical = toolList.filter((tool) => tool.risk === 'critical');

    expect(critical.map((tool) => tool.name)).toEqual(['fennedash_system_clear_cache']);
  });

  it('exposes exactly one critical approval entry point', () => {
    const approvals = toolList.filter((tool) => tool.module === 'approvals');

    expect(approvals.map((tool) => tool.name)).toEqual(['fennedash_approvals_request']);
    expect(approvals[0]?.risk).toBe('write');
  });

  it('keeps resource names and URIs unique', () => {
    const names = resources.list().map((resource) => resource.name);
    const uris = resources
      .list()
      .map((resource) => (resource.kind === 'static' ? resource.uri : resource.uriTemplate));

    expect(new Set(names).size).toBe(names.length);
    expect(new Set(uris).size).toBe(uris.length);
    expect(uris).toContain('fennedash://projects/{id}');
    expect(uris).toContain('fennedash://clients/{id}');
  });

  it('builds prompts with the supplied arguments', () => {
    const diagnose = prompts.list().find((prompt) => prompt.name === 'diagnose_project');
    assert(diagnose);

    const result = diagnose.build({ project_id: '7' }, {} as never);
    const text = result.messages[0]?.content;

    assert(text && typeof text === 'object' && 'text' in text);
    expect(String(text.text)).toContain('project 7');
    expect(String(text.text)).toContain('fennedash_projects_diagnose');
    expect(result.messages[0]?.role).toBe('user');
  });
});
