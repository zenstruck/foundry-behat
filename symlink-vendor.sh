#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MONOREPO_ROOT="$(cd "${SCRIPT_DIR}/../../.." && pwd)"
ROOT_SRC="${MONOREPO_ROOT}/src"
VENDOR_SRC="${SCRIPT_DIR}/vendor/zenstruck/foundry/src"

# in a standalone clone of the split zenstruck/foundry-behat repository, ../../.. escapes the
# clone: replacing the real vendor sources with dangling symlinks would break the installation
if ! grep -q '"name": "zenstruck/foundry"' "${MONOREPO_ROOT}/composer.json" 2>/dev/null; then
    echo "Not inside the zenstruck/foundry monorepo: skipping vendor symlinks."
    exit 0
fi

if [ ! -d "${VENDOR_SRC}" ]; then
    echo "Directory vendor/zenstruck/foundry/src does not exist yet."
    exit 1
fi

for item in "${ROOT_SRC}"/*; do
    name=$(basename "$item")

    if [ "$name" = "Test" ]; then
        continue
    fi

    target="${VENDOR_SRC}/${name}"

    rm -rf "$target"

    ln -s "$item" "$target"
done

echo -e "\nSymlinks created in ${VENDOR_SRC}\n"
