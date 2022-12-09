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
                  <tr>
                    <td>番号</td>
                    <td>日付</td>
                    <td>金額</td>
                    <td>取引先</td>
                    <td>種別</td>
                    <td>&nbsp;</td>
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
              <td>&nbsp;</td>
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
          </nav>
        </div>
      </div>
    </div>
  </div>
{% endblock %}
