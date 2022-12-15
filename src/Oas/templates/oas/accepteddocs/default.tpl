{% extends "master.tpl" %}

{% block head %}
  <script src="{{ config.global.assets_path }}script/oas/accepted_document.js"></script>
{% endblock %}

{% block main %}
  {% set appname = apps.currentApp('basename') %}
  <div class="explorer">
    <div class="explorer-mainframe">
      <div class="explorer-list">
        <h2 class="headline">受領書類一覧</h2>
        <div class="explorer-body">
          {% for doc in docs %}
            {% if loop.first %}
              <table>
                <thead>
                  <tr data-order="{{ order_cookie }}" data-sort="{{ sort_cookie }}">
                    <td class="change-sort" data-column="sequence">番号</td>
                    <td class="change-sort" data-column="receipt_date">日付</td>
                    <td class="change-sort" data-column="price">金額</td>
                    <td class="change-sort" data-column="sender">取引先</td>
                    <td class="change-sort" data-column="category">種別</td>
                    <td>備考</td>
                  </tr>
                </thead>
                <tbody id="document-list">
            {% endif %}
            <tr data-id="{{ doc.id }}">
              <td>{{ doc.sequence }}</td>
              <td>{{ doc.receipt_date|date('Y年m月d日') }}</td>
              <td>{{ doc.price|number_format }}</td>
              <td>{{ doc.sender }}</td>
              <td>{{ doc.category }}</td>
              <td>{{ doc.source }}</td>
            </tr>
            {% if loop.last %}
                </tbody>
              </table>
            {% endif %}
          {% else %}
            <div class="empty-list">該当するファイルはありません</div>
          {% endfor %}
        </div>
        <div class="footer-controls">
          <nav class="links flexbox">
            {% if apps.hasPermission('oas.accepteddocs.create') %}
              <a href="?mode=oas.accepted-docs.response:add-file" class="subform-opener"><mark>＋</mark>新規書類</a>
            {% endif %}
            <input type="text" name="search_query" id="search-query-1" class="search-query"{% if queryString %} value="{{ queryString }}"{% endif %}>
            <a href="?mode=oas.accepted-docs.response:search-options" class="normal-link options subform-opener">詳細検索</a>
          </nav>
          <nav class="pagination">
            {% include 'pagination.tpl' %}
            {% set rows = [30,50,100,150] %}
            {% if pager.records > rows[0] %}
              {% for row in rows %}
                {% if loop.first %}
                  <select name="rows_per_page_accepted_document" class="setcookie-and-reload">
                {% endif %}
                    <option value="{{ row }}"{% if rows_per_page == row %} selected{% endif %}>{{ row }}件表示</option>
                {% if loop.last %}
                  </select>
                {% endif %}
              {% endfor %}
            {% endif %}
          </nav>
        </div>
      </div>
    </div>
  </div>
{% endblock %}
