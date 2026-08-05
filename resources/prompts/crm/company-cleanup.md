# Company Cleanup Rules (AI Structured)

Normalize this company data according to the rules below.
Return only cleaned values in the output schema fields.

## Rules

1. Normalize nullable strings
- Trim whitespace
- Collapse repeated whitespace
- Convert empty strings to null

2. Website
- Normalize to canonical URL when possible
- If missing protocol, add `https://`
- Leave null when no valid URL

3. Country normalization
- Normalize all United States variants to `USA`
- Keep other countries as cleaned strings

4. Address handling
- If `address_line1` includes a full address (`street, city, ST ZIP, country`), parse and split
- If `state` contains combined `ST ZIP`, split to `state` and `postal_code`
- If `city` contains trailing country segment, move it to `country` when appropriate

5. State formatting
- Use uppercase 2-letter abbreviations when applicable

## Output contract

Return values for these keys only:
- website
- address_line1
- address_line2
- city
- state
- postal_code
- country
- email
- phone

Set unknown values to null.

Company JSON:
{{company_json}}
