<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Directory Tree</title>
  <link href="providers.css" rel="stylesheet" />
  <link href="style.css" rel="stylesheet" />

  <script src="providers.js"></script>
  <style>
    /* Loading animation styles */
    .loader {
      border: 5px solid #f3f3f3;
      border-top: 5px solid #3498db;
      border-radius: 50%;
      width: 30px;
      height: 30px;
      animation: spin 1s linear infinite;
      margin: 20px auto;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    .loading-text {
      text-align: center;
      color: #666;
      font-style: italic;
      margin: 10px 0;
    }
  </style>
</head>

<body>
  <div id="sidebar">
    <h1>Directory Tree</h1>
    <div id="tree">
      <div id="tree-loader" class="loader"></div>
      <p id="tree-loading-text" class="loading-text">Loading directory structure...</p>
    </div>
  </div>
  <div id="content">
    <h1>File Details</h1>
    <p id="file-content">Select a file to view its content here.</p>
  </div>

  <script>
    function buildTree(paths) {
      const tree = {};
      paths.forEach(path => {
        const parts = path.split('/').filter(Boolean);
        let current = tree;
        parts.forEach((part, index) => {
          if (!current[part]) {
            current[part] = index === parts.length - 1 ? null : {};
          }
          current = current[part];
        });
      });
      return tree;
    }

    function createTreeElement(obj, parentPath = '') {
      const ul = document.createElement('ul');
      for (const key in obj) {
        const li = document.createElement('li');
        const currentPath = `${parentPath}/${key}`;

        if (obj[key] === null) {
          li.textContent = key;
          li.classList.add('file');
          li.onclick = () => {
            showFileLoading();
            load_file(currentPath);
          };
        } else {
          const span = document.createElement('span');
          const indicator = document.createElement('span');
          indicator.textContent = '▶';
          indicator.classList.add('indicator');

          span.textContent = key;
          span.classList.add('folder');
          span.onclick = () => {
            childUl.classList.toggle('hidden');
            indicator.textContent = childUl.classList.contains('hidden') ? '▶' : '▼';
          };

          li.appendChild(indicator);
          li.appendChild(span);

          const childUl = createTreeElement(obj[key], currentPath);
          childUl.classList.add('hidden');
          li.appendChild(childUl);
        }
        ul.appendChild(li);
      }
      return ul;
    }

    function showTreeLoading() {
      document.getElementById('tree').innerHTML = `
        <div id="tree-loader" class="loader"></div>
        <p id="tree-loading-text" class="loading-text">Loading directory structure...</p>
      `;
    }

    function hideTreeLoading() {
      document.getElementById('tree-loader')?.remove();
      document.getElementById('tree-loading-text')?.remove();
    }

    function showFileLoading() {
      document.getElementById('file-content').innerHTML = `
        <div class="loader"></div>
        <p class="loading-text">Loading file details...</p>
      `;
    }

    async function get_file_list(collection) {
      showTreeLoading();
      try {
        const response = await fetch(`show_collection_files_callback.php?cmd=file_list&collection=${collection}`);
        const data = await response.json();
        return data;
      } catch (error) {
        console.error("Error fetching file list:", error);
        document.getElementById('tree').innerHTML = `<p style="color: red;">Error loading directory structure. Please try again.</p>`;
        return [];
      }
    }

    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    const collection = urlParams.get('collection');

    let data = get_file_list(collection).then(data => {
      if (data && data.length > 0) {
        document.getElementById('tree').innerHTML = ''; // Clear loading animation
        const treeData = buildTree(data);
        const treeElement = createTreeElement(treeData);
        document.getElementById('tree').appendChild(treeElement);
      } else {
        document.getElementById('tree').innerHTML = '<p>No files found or error loading directory.</p>';
      }
    });

    async function load_file(currentPath) {
      try {
        const encodedPath = encodeURIComponent(currentPath);
        const response = await fetch(`show_collection_files_callback.php?cmd=file_details&filename=${encodedPath}`);
        const data = await response.json();

        let fileContent = `
          <h2>File Information</h2><br>
          <b>ID:</b> ${data["relative_path"]}<br>
          <b>SHA1:</b> ${data["sha1"]}<br>
        `;
        
        if (data["ipfs_cid"]) {
          fileContent += `<b>CID (Kubo):</b> <a target="_blank" href="https://cid.ipfs.tech/#${data["ipfs_cid"]}">${data["ipfs_cid"]}</a><br>`;
        }
        
        if (data["key_fp"]) {
          fileContent += `<b>PGP Key:</b> ${data["key_fp"]}<br>
          <b>Crypto Key:</b> <span class="truncate-text">${data["encrypted_key"]}</span><br>
          <br>
          `;
        }

        if (data["singularity"] && data["singularity"]['sectors']) {
          fileContent += `<b>CID (Singularity):</b> <a target="_blank" href="https://cid.ipfs.tech/#${data["singularity"]["cid"]}">${data["singularity"]["cid"]}</a><br>
          <h2>Filecoin Deals</h2>
          ${displayRanges_singularity(data['singularity']['sectors'])}`;
        }
        
        if (data["ipfs_cid"]) {
          fileContent += `<h2>IPFS Copies</h2>
          <div id="providers"></div>
          <a href="https://w3s.link/ipfs/${data['ipfs_cid']}/">View on IPFS</a>`;
          document.getElementById('file-content').innerHTML = fileContent;
          showProviders(data["ipfs_cid"], document.getElementById("providers")); // Runs async
        } else {
          document.getElementById('file-content').innerHTML = fileContent;
        }
      } catch (error) {
        console.error("Error loading file details:", error);
        document.getElementById('file-content').innerHTML = `<p style="color: red;">Error loading file details. Please try again.</p>`;
      }
    }

    function displayRanges_singularity(sectors) {
      res = "<table><tr><th>CID</th><th>Deals</th></tr>";

      sectors.forEach((sector, index) => {
        res += `<tr><td><a target="_blank" href="https://cid.ipfs.tech/#${sector.cid}">${sector.cid}</a></td><td>`;

        deal_test=0;
            for (let index = 0; index < sector["deals"].length; index++) {
              let status="";
              let deal=sector["deals"][index];
              if (deal["state"]=="active") {
                let end_epoch=heightToUnix(deal["end_epoch"]);
                status=`<a target="_blank" href="https://filecoin.tools/search?q=` + sector['cid'] + `"><span class="deal" title="Verified ${deal["last_verified_at"]} Expires: ${unixToHuman(end_epoch)} Provider: ${deal["provider"]}">✅ ${deal["deal_id"]}<br></span></a>`;
              } else {
                let end_epoch=heightToUnix(deal["end_epoch"]);
                status=`<span class="dealred" title="Verified ${deal["last_verified_at"]} Expires: ${unixToHuman(end_epoch)}">❌ ${deal["deal_id"]}</span>`;
              }
            deal_test=`${status} `         
            res += deal_test
      }
        res += `</td></tr>`;
      });
      res += `</table>`;
      return res;
    }

    const FILECOIN_GENESIS_UNIX_EPOCH = 1598306400;
    function heightToUnix (filEpoch) {
      return (filEpoch * 30) + FILECOIN_GENESIS_UNIX_EPOCH
    }
    
    function unixToHuman(timestamp) {
      const date = new Date(timestamp * 1000); // Convert from seconds to milliseconds
      return date.toLocaleString(); // Convert to human-readable format
    } 
    
    function formatBytes(bytes, decimals = 2) {
      if (bytes === 0) return '0 B';
      const k = 1024;
      const sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return `${parseFloat((bytes / Math.pow(k, i)).toFixed(decimals))} ${sizes[i]}`;
    }
  </script>  
</body>

</html>
