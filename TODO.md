# Open todos

- [ ] app/Models/Cloning.php - Make the trigger_config a structured DTO
- [ ] Trigger via API should be displayed on cloning detail page as a replacement for the Schedule Card. The new card should display the title "Trigger" and then it displays schedule, api trigger and the manual trigger
- [ ] Add a card with the link to the latest audit log (last successful run has one) at the cloning detail page
- [ ] Incoming API Trigger URL has to be displayed on the detail page of a cloning
- [ ] Webhooks has to be displayed on the detail page of a cloning
- [ ] Improve the design layout of a cloning detail page
- [ ] Change the clonings index page (/clonings) table column "Schedule" to "Trigger" and display the schedule, manual and api trigger - Manual just when no other trigger is possible
- [ ] Add the information of the initiator of the run to the started column on page /cloning-runs: by user, api, scheduler - maybe just with an icon to show it
- [ ] Add a button to delete all Failed runs within the cloning detail page -> this should delete all failed runs for this cloning
- [ ] Add a button to delete all Failed runs within the cloning runs (/cloning-runs) -> this should delete all failed runs
- [ ] Support the update of a connection within the connection management (/connections) - as form sheet like on the creation of it
- [ ] change the input field colors: in light theme there should be a white background of the input field
- [ ] listen to all sql queries and log them to Log::debug() so I can check the processing of a cloning run
- [ ] Webhook calls should be visible on the cloning-run detail page - not in the execution log, but below as separate information: "Webhook for failure/success gets triggered" (or similar) and displayed with success or error if the webhook could be called or not
- [ ] Clonings Table is not scrollable horizontically on small screens
- [ ] Toggling light and dark theme: application has a mechanic which should be taken over to the /docs layout. So it stays the same on opening the docs page from the app
- [ ] add documentation content: screenshots are available in /docs-screenshots and in /docs-files is a json export of a cloning run
    - [ ] Structure the whole documentation new from groundup - the current content is just example content to showcase the features
        - first level under /docs is the chapter directory level. they get ordered by the numeric prefix and it will render at http://localhost:8000/docs
        - within a chapter folder there are the markdown content files with frontmatter (title and introduction is necessary)
        - When you think there is something missing, please implement that behaviour accordingly with tests
        - Target group for reading the documentation is normal users with technical background. Document it like a journey for the user reading it
    - [ ] Copy any necessary images or files into the chapter folder and give feedback if you want me to shot a necessary image (what should be shown on the image)

## GitHub
- [ ] GitHub Profile Page anlegen
