"""Seen-red control: rewrite an INSTALLED consent.js so held scripts are all
inserted at once (no waiting for a non-async external script to load). The Pro
'blocked scripts' test's order assertion must then fail.
Usage: python3 mutate-order.py <path to installed consent.js>"""
import sys
p = sys.argv[1]
s = open(p).read()
a = "wait = ! s.async;"
assert a in s, "mutation target not found"
open(p, 'w').write(s.replace(a, "wait = false;"))
print("MUTATION applied: held scripts no longer wait for load")
