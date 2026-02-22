---
title: Pipeline Integration
excerpt: Trigger a Clonio cloning run from any CI/CD pipeline using a single POST request with curl or wget.
---

# Pipeline Integration

Every cloning configuration exposes a unique trigger URL. Sending a **POST** request to that URL starts a cloning run immediately — no special tooling, no SDK required. A plain `curl` or `wget` call is all you need.

## The Trigger URL

You can find your trigger URL in the cloning configuration detail view. It looks like this:

```
https://<your-clonio-instance>/api/trigger/5f23fcede47385479ab59ca4e5d5de978911658fcd677480dce13076fe40f75c
```

The long token at the end is unique per configuration and acts as its authentication credential. Keep it secret — anyone with the URL can trigger a run.

A successful POST returns HTTP `200` and starts the cloning run asynchronously. You can monitor progress in the **Executions** view or via the [audit log](./02-audit-log.md).

---

## GitHub Actions

```yaml
name: Clone production DB to staging

on:
  push:
    branches:
      - main

jobs:
  clone-database:
    runs-on: ubuntu-latest
    steps:
      - name: Trigger Clonio cloning run
        run: |
          curl --fail --silent --show-error --request POST \
            "https://<your-clonio-instance>/api/trigger/${{ secrets.CLONIO_TRIGGER_TOKEN }}"
```

Store the token part of your trigger URL as a GitHub Actions secret named `CLONIO_TRIGGER_TOKEN`.

---

## GitLab CI

```yaml
stages:
  - deploy
  - clone-db

clone-database:
  stage: clone-db
  image: alpine
  before_script:
    - apk add --no-cache curl
  script:
    - curl --fail --silent --show-error --request POST
        "https://<your-clonio-instance>/api/trigger/$CLONIO_TRIGGER_TOKEN"
  only:
    - main
```

Add `CLONIO_TRIGGER_TOKEN` as a CI/CD variable in your GitLab project settings (Settings → CI/CD → Variables).

---

## Jenkins

Add a shell build step or a pipeline stage to your `Jenkinsfile`:

```groovy
pipeline {
    agent any

    stages {
        stage('Clone database') {
            steps {
                withCredentials([string(credentialsId: 'clonio-trigger-token', variable: 'CLONIO_TRIGGER_TOKEN')]) {
                    sh '''
                        curl --fail --silent --show-error --request POST \
                          "https://<your-clonio-instance>/api/trigger/${CLONIO_TRIGGER_TOKEN}"
                    '''
                }
            }
        }
    }
}
```

Store the token in Jenkins Credentials (Manage Jenkins → Credentials) as a **Secret text** entry with the ID `clonio-trigger-token`.

---

## CircleCI

```yaml
version: 2.1

jobs:
  clone-database:
    docker:
      - image: cimg/base:stable
    steps:
      - run:
          name: Trigger Clonio cloning run
          command: |
            curl --fail --silent --show-error --request POST \
              "https://<your-clonio-instance>/api/trigger/${CLONIO_TRIGGER_TOKEN}"

workflows:
  deploy-and-clone:
    jobs:
      - clone-database:
          filters:
            branches:
              only: main
```

Add `CLONIO_TRIGGER_TOKEN` as an environment variable in your CircleCI project settings (Project Settings → Environment Variables).

---

## Bitbucket Pipelines

```yaml
pipelines:
  branches:
    main:
      - step:
          name: Trigger Clonio cloning run
          image: alpine
          script:
            - apk add --no-cache curl
            - curl --fail --silent --show-error --request POST
                "https://<your-clonio-instance>/api/trigger/$CLONIO_TRIGGER_TOKEN"
```

Add `CLONIO_TRIGGER_TOKEN` as a repository variable in Bitbucket (Repository settings → Pipelines → Repository variables).

---

## Azure DevOps

```yaml
trigger:
  branches:
    include:
      - main

pool:
  vmImage: ubuntu-latest

steps:
  - task: Bash@3
    displayName: Trigger Clonio cloning run
    inputs:
      targetType: inline
      script: |
        curl --fail --silent --show-error --request POST \
          "https://<your-clonio-instance>/api/trigger/$(CLONIO_TRIGGER_TOKEN)"
```

Add `CLONIO_TRIGGER_TOKEN` as a pipeline variable in Azure DevOps (Pipelines → Edit → Variables).

---

## Drone CI

```yaml
kind: pipeline
type: docker
name: clone-database

trigger:
  branch:
    - main

steps:
  - name: Trigger Clonio cloning run
    image: alpine
    commands:
      - apk add --no-cache curl
      - curl --fail --silent --show-error --request POST
          "https://<your-clonio-instance>/api/trigger/$$CLONIO_TRIGGER_TOKEN"
```

Set `CLONIO_TRIGGER_TOKEN` as a secret in your Drone server and reference it in the step's `environment` block if your Drone version requires explicit secret binding.

---

## Cron / Scheduled Runs

You can also schedule a cloning run with a standard cron job on any Linux server:

```bash
# Run every night at 02:00
0 2 * * * curl --fail --silent --show-error --request POST \
  "https://<your-clonio-instance>/api/trigger/5f23fcede47385479ab59ca4e5d5de978911658fcd677480dce13076fe40f75c" \
  >> /var/log/clonio-trigger.log 2>&1
```

---

## Handling Errors

All examples above use `--fail` with curl, which causes the command to exit with a non-zero status code if the server returns an HTTP error. This ensures your pipeline step fails visibly when the trigger does not succeed.

If you prefer `wget`:

```bash
wget --quiet --server-response --post-data="" \
  "https://<your-clonio-instance>/api/trigger/5f23fcede47385479ab59ca4e5d5de978911658fcd677480dce13076fe40f75c"
```

The cloning run itself is asynchronous — the API call returns as soon as the run is queued. Use the [audit log](./02-audit-log.md) to verify the outcome.
