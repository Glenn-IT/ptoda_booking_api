# PTODA Docs — Prompt Templates

> Copy-paste these prompts into GitHub Copilot Chat whenever you need to update the docs.
> Always attach or open the relevant file(s) before sending the prompt.

---

## 1. New Endpoint Added

```
The following endpoint is now final and tested. Update the docs:

- Method: [GET | POST | PUT | DELETE]
- URL: /[endpoint]
- Role required: [passenger | driver | admin | any]
- Auth required: [yes | no]
- Backend file: controllers/[FileName].php
- Request body: [fields, or "none"]
- Success response: [HTTP code + JSON shape]
- Error responses: [list of HTTP codes + messages]

Files to update:
- docs/api/[FILE].md → add new endpoint section
- docs/INDEX.md → add row to the endpoints table
- docs/models/[FILE].md → if request/response has new fields
```

---

## 2. Bug Fixed

```
Log this bug fix in docs/BUGS_AND_FIXES.md:

- Title: [short description]
- Date: [YYYY-MM-DD]
- Phase: [Phase N — section name]
- File(s) affected: [path/to/file.php or File.kt]
- Description: [what went wrong]
- Root cause: [why it happened]
- Fix applied: [what was changed]
- Prevention tip: [how to avoid in the future]
```

---

## 3. Database Table Changed

```
Update the docs for a database change:

- Table: [table_name]
- Change: [added column / removed column / changed type / new table]
- Column details: [name, type, nullable, default, purpose]

Files to update:
- docs/models/[FILE].md → MySQL table + Column Reference + Kotlin data class
- docs/api/[FILE].md → if the response shape changed
```

---

## 4. Kotlin Data Class Changed

```
The following Kotlin data class needs to be updated in the docs:

- Data class: [ClassName]
- Doc file: docs/models/[FILE].md  (or docs/api/[FILE].md)
- Change: [added field / removed field / changed type]
- Field details: [name: Type — reason]
```

---

## 5. Full Feature Done & Tested

```
The [feature name] feature is now final and fully tested.
Update all relevant docs to reflect the current state:

Backend changes:
- New/updated endpoint: [method + URL]
- Backend files changed: [list]
- DB changes: [table + column if any]

Android changes:
- Data class changed: [class name + field]
- ApiService method: [method name]
- Flow affected: [auth / booking / driver approval / other]

Update these doc files:
- docs/api/[FILE].md
- docs/models/[FILE].md
- docs/flows/[FILE].md
- docs/INDEX.md (if endpoint table needs a new row)
```

---

## 6. Single File Quick Update

```
Update docs/[folder]/[FILE].md only:

- Section: [section heading in the file]
- Change: [describe exactly what to add, remove, or edit]
```

---

## 7. New Flow Documented

```
Add a new flow doc at docs/flows/[FLOW_NAME].md:

- Flow name: [name]
- Actors: [Passenger / Driver / Admin / System]
- Steps: [describe the sequence]
- Endpoints involved: [list]
- DB tables involved: [list]
- Related docs: [api/FILE.md, models/FILE.md]
```

---

## 8. Sync Check (Audit)

```
Do a full sync check across the docs folder.
Read all files in docs/api/, docs/models/, and docs/flows/
and check for any inconsistencies:

- Endpoint listed in INDEX.md but missing in api/*.md
- Kotlin data class field that doesn't match the DB column
- Flow diagram that references an endpoint not in api/*.md
- Sync Rules table that is incomplete

Report what is out of sync and fix it.
```

---

_Last updated: 2026-03-18_
