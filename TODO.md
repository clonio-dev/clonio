# Open todos

- [ ] app/Models/Cloning.php - Make the trigger_config a structured DTO
- [ ] Trigger via API should be displayed on cloning detail page as a replacement for the Schedule Card. The new card should display the title "Trigger" and then it displays schedule, api trigger and the manual trigger
- [ ] Add a card with the link to the latest audit log (last successful run has one) at the cloning detail page
- [ ] Improve the design layout of a cloning detail page
- [ ] Change the clonings index page (/clonings) table column "Schedule" to "Trigger" and display the schedule, manual and api trigger - Manual just when no other trigger is possible
- [ ] Add the information of the initiator of the run to the started column on page /cloning-runs: by user, api, scheduler - maybe just with an icon to show it
- [ ] Add a button to delete all Failed runs within the cloning detail page -> this should delete all failed runs for this cloning
- [ ] Add a button to delete all Failed runs within the cloning runs (/cloning-runs) -> this should delete all failed runs
- [ ] Support the update of a connection within the connection management (/connections) - as form sheet like on the creation of it
- [ ] add documentation content
- [ ] change the input field colors: in light theme there should be a white background of the input field
- [ ] listen to all sql queries and log them to Log::debug() so I can check the processing of a cloning run
