---
title: AI & LLM Integration
excerpt: How to feed Clonio's documentation to an LLM and let AI agents trigger cloning runs autonomously as part of automated workflows.
---

# AI & LLM Integration

Clonio is built to work naturally inside AI-driven and LLM-orchestrated workflows. The documentation is always available as plain Markdown, making it easy for any language model to read, understand, and act on. Triggering a cloning run requires nothing more than a single HTTP POST — a task any AI agent or tool-calling LLM can perform directly.

---

## Reading the Docs as Markdown

Every documentation page in Clonio is available as raw Markdown. Two ways to get it:

**Append `.md` to any docs URL:**

```
https://<your-clonio-instance>/docs/getting-started/introduction.md
https://<your-clonio-instance>/docs/cloning-runs/pipeline-integration.md
```

**Or request the page normally** — the response is already structured Markdown under the hood and can be consumed directly by any tool that reads URLs.

This means you can point an LLM at any Clonio docs page and it will receive clean, token-efficient Markdown — no HTML parsing, no noise, no wasted context.

---

## Feeding Docs to an LLM

To give an LLM full context about how Clonio works, fetch one or more doc pages and include them in the system prompt or context window. Useful pages to include depending on the task:

| Goal | Doc page |
|------|----------|
| Understand Clonio end-to-end | `getting-started/introduction.md` |
| Configure a new cloning | `clonings/creating-a-cloning.md` |
| Understand anonymization options | `clonings/anonymization.md` |
| Trigger runs from a pipeline | `cloning-runs/pipeline-integration.md` |
| Read the audit trail | `cloning-runs/audit-log.md` |

Example — fetching a page into a shell variable for use in a prompt:

```bash
CLONIO_DOCS=$(curl --silent \
  "https://<your-clonio-instance>/docs/cloning-runs/pipeline-integration.md")
```

You can then pass `$CLONIO_DOCS` as part of a system message to any LLM API (OpenAI, Anthropic, etc.).

---

## Triggering a Cloning Run from an AI Agent

An AI agent that has been given the trigger URL can start a cloning run autonomously with a single tool call or shell command. The API requires only a POST request — no body, no authentication headers beyond the token already embedded in the URL.

**Minimal trigger:**

```bash
curl --silent --fail --request POST \
  "https://<your-clonio-instance>/api/trigger/5f23fcede47385479ab59ca4e5d5de978911658fcd677480dce13076fe40f75c"
```

This is intentionally simple so that any agent runtime — LangChain, CrewAI, AutoGen, Claude, custom tool loops — can execute it without a custom integration layer.

---

## Example: Claude as an Orchestrator

Give Claude (or any instruction-following LLM) the trigger URL and it can decide when and whether to clone based on context:

```
System prompt:
  You are a deployment assistant. After a successful production deployment,
  always trigger a database clone to staging using this URL:
  https://<your-clonio-instance>/api/trigger/<token>

  Use curl with --request POST to trigger it. Confirm success if HTTP 200 is returned.
```

The LLM can call a `bash` or `shell` tool with the curl command above, check the response code, and report back — no custom plugin or SDK required.

---

## Example: LangChain Tool

If you are building a LangChain agent, you can wrap the trigger as a simple tool:

```python
from langchain.tools import tool
import requests

@tool
def trigger_clonio_clone() -> str:
    """Triggers a Clonio database cloning run for the staging environment."""
    response = requests.post(
        "https://<your-clonio-instance>/api/trigger/<token>"
    )
    if response.status_code == 200:
        return "Cloning run started successfully."
    return f"Trigger failed with status {response.status_code}."
```

Register it in your agent's tool list and the LLM will call it whenever the context warrants a fresh database clone.

---

## Example: System Prompt Snippet

Include this in any agent that manages deployments or test environments:

```
You have access to Clonio, a GDPR-compliant database cloning tool running inside our infrastructure.

To start a cloning run that copies production data to staging with anonymization applied:
  POST https://<your-clonio-instance>/api/trigger/<token>

A 200 response means the run has been queued. The clone runs asynchronously.
Trigger a clone whenever:
- A staging deployment has completed
- A developer requests fresh test data
- A weekly refresh is due

Documentation is available at:
  https://<your-clonio-instance>/docs/getting-started/introduction.md
```

---

## Token Efficiency

All Clonio docs pages are written in concise Markdown without unnecessary HTML, JavaScript, or navigation chrome. When fetched via the `.md` URL, the response contains only the content — making it practical to include multiple pages in a single LLM context window without blowing the token budget.

A full docs page typically fits within 1,000–3,000 tokens, leaving ample room for conversation, reasoning, and tool calls.

---

## llms.txt

Clonio also exposes an `llms.txt` file at the root of your instance, following the [llms.txt standard](https://llmstxt.org/). This gives LLMs a structured index of everything available:

```
https://<your-clonio-instance>/llms.txt
```

Point an LLM at this file first to discover all available documentation pages, then fetch specific pages for detailed context.
