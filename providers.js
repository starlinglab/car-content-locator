const providerData = [
  { url: "https://delegated-ipfs.dev", name: "DHT+IPNI" },
  { url: "https://indexer.pinata.cloud", name: "Pinata" },
  { url: "https://routingv1.storacha.network", name: "Storacha" },
  // { url: "https://routingv1.filebase.io", name: "Filebase" }, // Doesn't have CORS
  { url: "https://cid.contact", name: "IPNI" }, // Already captured by delegated-ipfs.dev
];
const TIMEOUT = 30_000; // ms

/**
 * Displays providers for a given CID in the specified HTML element.
 *
 * @async
 * @function showProviders
 * @param {string} cid - The Content Identifier (CID) to fetch providers for
 * @param {HTMLElement} div - The HTML element (typically a div) where providers will be displayed
 * @returns {Promise<void>} A promise that resolves when the providers are successfully displayed
 * @throws {Error} Throws an error if the CID is invalid or if the div element is null
 */
async function showProviders(cid, div) {
  div.innerHTML += "<h3>Providers</h3><p>Loading... (up to 30s)</p>";

  const fetchPromises = providerData.map(async (urlObj) => {
    try {
      const cidUrl = urlObj.url + `/routing/v1/providers/${cid}`;
      const response = await fetch(cidUrl, {
        signal: AbortSignal.timeout(TIMEOUT),
      });
      if (!response.ok) {
        if (response.status === 404) {
          // No providers found
          return {
            name: urlObj.name,
            cidUrl: cidUrl,
            providerCount: 0,
            success: true,
          };
        }
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      const data = await response.json();

      // Extract Providers array length, default to 0 if not found
      const providerCount = Array.isArray(data.Providers)
        ? data.Providers.length
        : 0;

      return {
        name: urlObj.name,
        cidUrl: cidUrl,
        providerCount: providerCount,
        success: true,
      };
    } catch (error) {
      return {
        name: urlObj.name,
        cidUrl: cidUrl,
        providerCount: 0,
        success: false,
        error: error.message,
      };
    }
  });

  const results = await Promise.all(fetchPromises);

  // Remove loading message
  let html =
    "<h2>IPFS Copies</h2><table><tbody><tr><th>Indexer</th><th>No. Copies</th></tr>";

  results.forEach((result) => {
    if (result.success) {
      html += `<tr><td><a target="_blank" href="${result.cidUrl}">${result.name}</a></td><td><span class="deal">${result.providerCount}</span></td></tr>`;
    } else {
      html += `<tr><td><a target="_blank" href="${result.cidUrl}">${result.name}</a></td><td>${result.error}</td></tr>`;
    }
  });
  html += `</tbody></table>`;
  div.innerHTML = html;
}
