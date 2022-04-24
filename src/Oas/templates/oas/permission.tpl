<section class="permission" id="srm-permission">
  <h2><a href="#permission-editor-srm" class="accordion-switcher">アプリケーション権限設定</a></h2>
  <div id="permission-editor-srm" class="accordion">
    <table>
      <thead>
        <tr>
          <td>権限適用範囲</td>
          <td>作成</td>
          <td>読取</td>
          <td>更新</td>
          <td>削除</td>
          <td>その他</td>
        </tr>
      </thead>
      <tbody>
        <tr>
          <th>伝票</th>
          <td>{% if apps.userinfo.admin == 1 or priv.transfer.create == 1 %}<input type="checkbox" value="1" name="perm[{{ filters }}oas.transfer.create]"{% if post.perm[filters ~ 'oas.transfer.create'] == 1 %} checked{% endif %}>{% else %}-{% endif %}</td>
          <td>{% if apps.userinfo.admin == 1 or priv.transfer.read   == 1 %}<input type="checkbox" value="1" name="perm[{{ filters }}oas.transfer.read]"  {% if post.perm[filters ~ 'oas.transfer.read']   == 1 %} checked{% endif %}>{% else %}-{% endif %}</td>
          <td>{% if apps.userinfo.admin == 1 or priv.transfer.update == 1 %}<input type="checkbox" value="1" name="perm[{{ filters }}oas.transfer.update]"{% if post.perm[filters ~ 'oas.transfer.update'] == 1 %} checked{% endif %}>{% else %}-{% endif %}</td>
          <td>{% if apps.userinfo.admin == 1 or priv.transfer.delete == 1 %}<input type="checkbox" value="1" name="perm[{{ filters }}oas.transfer.delete]"{% if post.perm[filters ~ 'oas.transfer.delete'] == 1 %} checked{% endif %}>{% else %}-{% endif %}</td>
          <td>-</td>
        </tr>
        <tr>
          <th>税務処理</th>
          <td>{% if apps.userinfo.admin == 1 or priv.taxation.create == 1 %}<input type="checkbox" value="1" name="perm[{{ filters }}oas.taxation.create]"{% if post.perm[filters ~ 'oas.taxation.create'] == 1 %} checked{% endif %}>{% else %}-{% endif %}</td>
          <td>{% if apps.userinfo.admin == 1 or priv.taxation.read   == 1 %}<input type="checkbox" value="1" name="perm[{{ filters }}oas.taxation.read]"  {% if post.perm[filters ~ 'oas.taxation.read']   == 1 %} checked{% endif %}>{% else %}-{% endif %}</td>
          <td>{% if apps.userinfo.admin == 1 or priv.taxation.update == 1 %}<input type="checkbox" value="1" name="perm[{{ filters }}oas.taxation.update]"{% if post.perm[filters ~ 'oas.taxation.update'] == 1 %} checked{% endif %}>{% else %}-{% endif %}</td>
          <td>{% if apps.userinfo.admin == 1 or priv.taxation.delete == 1 %}<input type="checkbox" value="1" name="perm[{{ filters }}oas.taxation.delete]"{% if post.perm[filters ~ 'oas.taxation.delete'] == 1 %} checked{% endif %}>{% else %}-{% endif %}</td>
          <td>-</td>
        </tr>
        <tr>
          <th>固定資産</th>
          <td>{% if apps.userinfo.admin == 1 or priv.fixedasset.create == 1 %}<input type="checkbox" value="1" name="perm[{{ filters }}oas.fixedasset.create]"{% if post.perm[filters ~ 'oas.fixedasset.create'] == 1 %} checked{% endif %}>{% else %}-{% endif %}</td>
          <td>{% if apps.userinfo.admin == 1 or priv.fixedasset.read   == 1 %}<input type="checkbox" value="1" name="perm[{{ filters }}oas.fixedasset.read]"  {% if post.perm[filters ~ 'oas.fixedasset.read']   == 1 %} checked{% endif %}>{% else %}-{% endif %}</td>
          <td>{% if apps.userinfo.admin == 1 or priv.fixedasset.update == 1 %}<input type="checkbox" value="1" name="perm[{{ filters }}oas.fixedasset.update]"{% if post.perm[filters ~ 'oas.fixedasset.update'] == 1 %} checked{% endif %}>{% else %}-{% endif %}</td>
          <td>{% if apps.userinfo.admin == 1 or priv.fixedasset.delete == 1 %}<input type="checkbox" value="1" name="perm[{{ filters }}oas.fixedasset.delete]"{% if post.perm[filters ~ 'oas.fixedasset.delete'] == 1 %} checked{% endif %}>{% else %}-{% endif %}</td>
          <td>-</td>
        </tr>
        <tr>
          <th>総勘定元帳</th>
          <td>{% if apps.userinfo.admin == 1 or priv.ledger.create == 1 %}<input type="checkbox" value="1" name="perm[{{ filters }}oas.ledger.create]"{% if post.perm[filters ~ 'oas.ledger.create'] == 1 %} checked{% endif %}>{% else %}-{% endif %}</td>
          <td>{% if apps.userinfo.admin == 1 or priv.ledger.read   == 1 %}<input type="checkbox" value="1" name="perm[{{ filters }}oas.ledger.read]"  {% if post.perm[filters ~ 'oas.ledger.read']   == 1 %} checked{% endif %}>{% else %}-{% endif %}</td>
          <td>{% if apps.userinfo.admin == 1 or priv.ledger.update == 1 %}<input type="checkbox" value="1" name="perm[{{ filters }}oas.ledger.update]"{% if post.perm[filters ~ 'oas.ledger.update'] == 1 %} checked{% endif %}>{% else %}-{% endif %}</td>
          <td>{% if apps.userinfo.admin == 1 or priv.ledger.delete == 1 %}<input type="checkbox" value="1" name="perm[{{ filters }}oas.ledger.delete]"{% if post.perm[filters ~ 'oas.ledger.delete'] == 1 %} checked{% endif %}>{% else %}-{% endif %}</td>
          <td>-</td>
        </tr>
        <tr>
          <th>ファイル</th>
          <td>{% if apps.userinfo.admin == 1 or priv.file.create == 1 %}<input type="checkbox" value="1" name="perm[{{ filters }}oas.file.create]"{% if post.perm[filters ~ 'oas.file.create'] == 1 %} checked{% endif %}>{% else %}-{% endif %}</td>
          <td>{% if apps.userinfo.admin == 1 or priv.file.read   == 1 %}<input type="checkbox" value="1" name="perm[{{ filters }}oas.file.read]"  {% if post.perm[filters ~ 'oas.file.read']   == 1 %} checked{% endif %}>{% else %}-{% endif %}</td>
          <td>{% if apps.userinfo.admin == 1 or priv.file.update == 1 %}<input type="checkbox" value="1" name="perm[{{ filters }}oas.file.update]"{% if post.perm[filters ~ 'oas.file.update'] == 1 %} checked{% endif %}>{% else %}-{% endif %}</td>
          <td>{% if apps.userinfo.admin == 1 or priv.file.delete == 1 %}<input type="checkbox" value="1" name="perm[{{ filters }}oas.file.delete]"{% if post.perm[filters ~ 'oas.file.delete'] == 1 %} checked{% endif %}>{% else %}-{% endif %}</td>
          <td>{% if apps.userinfo.admin == 1 %}<label><input type="checkbox" value="1" name="perm[{{ filters }}oas.file.noroot]"{% if post.perm[filters ~ 'oas.file.noroot'] == 1 %} checked{% endif %}><small>最上位拒否</small></label>{% else %}-{% endif %}</td>
        </tr>
      </tbody>
    </table>
  </div>
</section>
