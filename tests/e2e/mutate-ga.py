"""Seen-red control: rewrite an INSTALLED consent.js so GA4 loads whatever the
choice (and on Reject). The 'nothing loads before consent' / Reject / GPC tests
must then fail. Usage: python3 mutate-ga.py <path to installed consent.js>"""
import sys
p = sys.argv[1]
s = open(p).read()
a = "if ( cfg.ga4 && choices.analytics && ! loaded.ga4 ) {"
assert a in s, "mutation target not found"
s = s.replace(a, "if ( cfg.ga4 && ! loaded.ga4 ) {")
s = s.replace("\t\tvar rec = current();\n\t\tif ( rec ) {", "\t\tloadTags( {} );\n\t\tvar rec = current();\n\t\tif ( rec ) {", 1)
open(p, 'w').write(s)
print("MUTATION applied: GA4 loads without consent")
