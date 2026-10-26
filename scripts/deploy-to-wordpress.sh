#!/usr/bin/env bash
set -e

PLUGIN_SLUG="kkhay"
SVN_URL="https://plugins.svn.wordpress.org/${PLUGIN_SLUG}"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TMP_DIR=$(mktemp -d /tmp/kkhay-svn-XXXXXX)

echo "==> Fetching WordPress.org SVN repository for '${PLUGIN_SLUG}'..."
svn checkout "${SVN_URL}" "${TMP_DIR}" --depth immediates
svn update --set-depth infinity "${TMP_DIR}/trunk"

echo "==> Syncing plugin files from ${ROOT_DIR} to trunk..."
cp -r "${ROOT_DIR}/assets" \
      "${ROOT_DIR}/includes" \
      "${ROOT_DIR}/languages" \
      "${ROOT_DIR}/kkhay.php" \
      "${ROOT_DIR}/readme.txt" \
      "${ROOT_DIR}/README.md" \
      "${ROOT_DIR}/LICENSE" \
      "${TMP_DIR}/trunk/"

echo "==> Creating release tag 1.0.4..."
mkdir -p "${TMP_DIR}/tags/1.0.4"
cp -r "${TMP_DIR}/trunk/"* "${TMP_DIR}/tags/1.0.4/"

cd "${TMP_DIR}"
svn add tags/1.0.4

echo "==> Current SVN status:"
svn status

echo "==> Submitting v1.0.4 to WordPress.org..."
svn commit -m "Tag v1.0.4 with Gateway naming, multi-chain support and kkhay contributor"

cd - > /dev/null
rm -rf "${TMP_DIR}"
echo "==> Done! Release v1.0.4 successfully submitted to https://wordpress.org/plugins/kkhay/"
