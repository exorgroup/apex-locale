"""Regenerate database/data/currencies.php from the ISO 4217 maintenance agency's list one.

    1. Download the list into an empty folder of its own (it is outside data, not code):
       https://www.six-group.com/dam/download/financial-information/data-center/iso-currrency/lists/list-one.xml
    2. python -I bin/iso4217.py <path-to>/list-one.xml database/data/currencies.php
    3. Review the diff, run the suite, and note the publication date in the CHANGELOG.

Keeps currencies with a numeric minor unit (drops metals and specials: XAU, XDR, XXX, XTS…),
drops funds (IsFund: CLF, BOV, CHE…), and removes duplicates by code. The seeder inserts only
what is missing and never overwrites, so a regenerated file adds new currencies to an existing
installation and leaves edited rows alone (LC1/003).
"""
import io
import sys
import xml.etree.ElementTree as ET


def main(src: str, out: str) -> None:
    root = ET.parse(src).getroot()
    published = root.get('Pblshd')

    seen = {}
    for entry in root.iter('CcyNtry'):
        code = entry.findtext('Ccy')
        units = entry.findtext('CcyMnrUnts')
        name = entry.find('CcyNm')
        if not code or not units or not units.isdigit():
            continue
        if name is not None and name.get('IsFund') == 'true':
            continue
        seen.setdefault(code, (code, entry.findtext('CcyNbr'), name.text.strip(), int(units)))

    rows = sorted(seen.values())

    w = io.StringIO()
    w.write("<?php\n\n/**\n * ISO 4217 active currencies: alphabetic code, numeric code, name, minor-unit decimals.\n")
    w.write(f" * Generated from the maintenance agency's list one (SIX, published {published}); funds and\n")
    w.write(r" * metals (no minor unit, or IsFund) are left out. Read by `Apex\Locale\Seeders\CurrencySeeder`," + "\n")
    w.write(" * which inserts what is missing and never overwrites (LC1/003). Regenerate: bin/iso4217.py.\n *\n")
    w.write(" * @return list<array{0: string, 1: string, 2: string, 3: int}> [code, numeric, name, decimals]\n */\nreturn [\n")
    for code, numeric, name, decimals in rows:
        escaped = name.replace("\\", "\\\\").replace("'", "\\'")
        w.write(f"    ['{code}', '{numeric}', '{escaped}', {decimals}],\n")
    w.write("];\n")

    with io.open(out, 'w', encoding='utf-8', newline='\n') as f:
        f.write(w.getvalue())

    by_decimals = {d: sum(1 for r in rows if r[3] == d) for d in sorted({r[3] for r in rows})}
    print(f"{len(rows)} currencies (published {published}); by decimals: {by_decimals}")


if __name__ == '__main__':
    if len(sys.argv) != 3:
        sys.exit('usage: python -I bin/iso4217.py <list-one.xml> <currencies.php>')
    main(sys.argv[1], sys.argv[2])
