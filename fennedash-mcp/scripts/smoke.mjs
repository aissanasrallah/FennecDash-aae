import { Client } from '@modelcontextprotocol/sdk/client/index.js';
import { StdioClientTransport } from '@modelcontextprotocol/sdk/client/stdio.js';

const transport = new StdioClientTransport({
  command: process.execPath,
  args: ['dist/src/index.js', '--transport', 'stdio'],
  cwd: new URL('..', import.meta.url).pathname.replace(/^\/([A-Za-z]:)/, '$1'),
  stderr: 'inherit',
});

const client = new Client({ name: 'smoke', version: '1.0.0' });

try {
  await client.connect(transport);
  console.log('SERVER', JSON.stringify(client.getServerVersion()));

  const tools = await client.listTools();
  console.log('TOOLS', tools.tools.length, tools.tools.slice(0, 4).map((t) => t.name).join(','));

  const resources = await client.listResources();
  console.log('RESOURCES', resources.resources.map((r) => r.uri).join(','));

  const templates = await client.listResourceTemplates();
  console.log('TEMPLATES', templates.resourceTemplates.map((r) => r.uriTemplate).join(','));

  const prompts = await client.listPrompts();
  console.log('PROMPTS', prompts.prompts.map((p) => p.name).join(','));

  const status = await client.callTool({ name: 'fennedash_system_get_status', arguments: {} });
  console.log('STATUS_OK', status.isError !== true, JSON.stringify(status.structuredContent).slice(0, 220));

  const projects = await client.callTool({
    name: 'fennedash_projects_list',
    arguments: { per_page: 2 },
  });
  console.log('PROJECTS_OK', projects.isError !== true, JSON.stringify(projects.structuredContent).slice(0, 220));

  const search = await client.callTool({
    name: 'fennedash_search_global',
    arguments: { q: 'client', limit: 2 },
  });
  console.log('SEARCH_OK', search.isError !== true, JSON.stringify(search.structuredContent).slice(0, 220));

  const resource = await client.readResource({ uri: 'fennedash://system/capabilities' });
  console.log('RESOURCE_OK', JSON.stringify(resource.contents[0]).slice(0, 200));

  const prompt = await client.getPrompt({ name: 'platform_health', arguments: { focus: 'errors' } });
  console.log('PROMPT_OK', prompt.messages.length, prompt.messages[0].content.text.slice(0, 120));

  const invalid = await client.callTool({
    name: 'fennedash_projects_get',
    arguments: { id: 'not-a-number' },
  });
  console.log('INVALID_IS_ERROR', invalid.isError === true);

  const missing = await client.callTool({
    name: 'fennedash_projects_get',
    arguments: { id: 999999 },
  });
  console.log('MISSING', JSON.stringify(missing.structuredContent).slice(0, 160));
} finally {
  await client.close();
}
