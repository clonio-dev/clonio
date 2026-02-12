---
title: Triggers and Scheduling
introduction: Configure manual execution, cron schedules, API triggers, and webhook notifications for your clonings.
---

# Triggers and Scheduling

Clonio supports multiple ways to trigger a cloning run and multiple ways to be notified of the result.

## Execution Methods

### Manual Execution

Every cloning can be triggered manually from the cloning detail page by clicking the **Run Now** button. This is the simplest way to run a cloning on demand.

### Scheduled Execution (Cron)

Enable scheduled execution to run clonings automatically at defined intervals. Clonio uses standard cron expressions under the hood.

**Simple mode** provides preset schedules and a visual frequency picker:

| Preset | Cron Expression |
|--------|----------------|
| Every hour | `0 * * * *` |
| Daily at midnight | `0 0 * * *` |
| Daily at 2 AM | `0 2 * * *` |
| Daily at 6 AM | `0 6 * * *` |
| Weekly on Sunday | `0 0 * * 0` |
| Weekly on Monday | `0 0 * * 1` |
| Monthly on 1st | `0 0 1 * *` |
| Weekdays at 3 AM | `0 3 * * 1-5` |

You can also set a custom frequency (Hourly, Daily, Weekly, Monthly) with specific hour and minute values.

**Advanced mode** accepts a raw cron expression for full flexibility. The next scheduled run is displayed below the input.

The clonings list shows the active schedule for each cloning, including the next run time.

### API Trigger

Enable the **Incoming API Trigger** to generate a unique URL that can be called from external systems. This is designed for CI/CD pipeline integration.

When enabled, Clonio generates a trigger URL:

```
POST http://your-clonio-instance/api/trigger/<token>
```

Example usage in a CI pipeline:

```bash
# Trigger a cloning run after deployment
curl -X POST https://clonio.example.com/api/trigger/241e7d67ee5196cfa8992b6c240eaba...
```

The trigger URL contains a cryptographically secure token. Keep it secret, as anyone with the URL can trigger a cloning run. If compromised, you can regenerate the token.

## Webhook Notifications

Webhooks let you notify external services when a cloning run completes. Two independent webhooks can be configured:

### Webhook on Success

Fires after every successful cloning run. Common use cases:

- Notify a Slack channel that fresh test data is available
- Trigger downstream processes that depend on updated staging data
- Update a status dashboard

### Webhook on Failure

Fires after every failed cloning run. Common use cases:

- Alert an on-call team via PagerDuty or Opsgenie
- Post to a monitoring channel in Slack or Microsoft Teams
- Log the failure in an external incident tracker

### Webhook Configuration

Each webhook accepts:

| Field | Description |
|-------|-------------|
| **URL** | The HTTP endpoint to call |
| **HTTP Method** | POST (default), GET, PUT, or PATCH |
| **Signing Secret** | Optional HMAC secret for verifying the webhook payload's authenticity |

### Webhook Payload Verification

When a signing secret is configured, Clonio signs the webhook payload using HMAC-SHA256. The receiving service can verify the signature to ensure the request genuinely came from Clonio and was not tampered with.

## Pausing a Cloning

Clonings can be paused from the clonings list. A paused cloning:

- Will not execute on its schedule
- Can still be triggered manually via the **Run Now** button
- Displays a "Paused" badge in the schedule column

Resume a paused cloning to re-enable scheduled execution.

## Next Steps

Learn how to [manage existing clonings](04-managing-clonings) or proceed to [Cloning Run Execution](/docs/3-cloning-runs/01-execution).
