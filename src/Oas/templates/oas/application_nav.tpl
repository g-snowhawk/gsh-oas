<li id="oas-transfer"><a href="?mode=oas.transfer.response">帳簿管理</a>
  <ul>
    <li><a href="?mode=oas.transfer.response&amp;t=T">振替伝票</a></li>
    <li><a href="?mode=oas.transfer.response&amp;t=R">入金伝票</a></li>
    <li><a href="?mode=oas.transfer.response&amp;t=P">出金伝票</a></li>
    <li><a href="?mode=oas.ledger">総勘定元帳</a></li>
    <li><a href="?mode=oas.fixedasset.response">固定資産台帳</a></li>
  </ul>
</li>
<li id="oas-taxation"><a href="?mode=oas.taxation.response">税務処理</a>
  <ul>
    <li><a href="?mode=oas.taxation.trialbalance">合計残高試算表</a></li>
    <li><a href="?mode=oas.taxation.socialinsurance">社会保険料等</a></li>
    {% if apps.hasPrivateData('financial.json', 'templates/oas/taxation', 1) != false %}
      <li><a href="?mode=oas.taxation.financial">決算書作成</a></li>
    {% endif %}
    {% if apps.hasPrivateData('tax_return_B.json', 'templates/oas/taxation', 1) != false %}
      <li><a href="?mode=oas.taxation.taxreturn">青色申告書作成</a></li>
    {% endif %}
  </ul>
</li>
{% if apps.hasPermission('oas.file.read', 0, 0, 'Do not use filesystem') %}
<li id="oas-taxation"><a href="?mode=oas.accepted-docs.response">受領書類管理</a>
<li id="oas-taxation"><a href="?mode=oas.filemanager.response">ファイル管理</a>
{% endif %}
