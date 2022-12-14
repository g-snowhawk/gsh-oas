{% extends "master.tpl" %}

{% block main %}
  <input type="hidden" name="mode" value="oas.taxation.socialinsurance:save">
  <div class="wrapper">
    <h1>社会保険料等に係る控除証明書等の記載事項</h1>
    <div class="fieldset">
      <label>種別</label>
      <select name="colnumber" required>
        <option value="">-- 以下より選択 --</option>
        <optgroup label="社会保険料">
          <option value="13-1"{% if '13-1' == post.colnumber %} selected{% endif %}>国民年金</option>
          <option value="13-2"{% if '13-2' == post.colnumber %} selected{% endif %}>国民健康保険</option>
          <option value="13-3"{% if '13-3' == post.colnumber %} selected{% endif %}>その他</option>
        </optgroup>
        <optgroup label="小規模企業共済等掛金">
          <option value="14-1"{% if '14-1' == post.colnumber %} selected{% endif %}>独立行政法人中小企業基盤整備機構の共済契約の掛金</option>
          <option value="14-2"{% if '14-2' == post.colnumber %} selected{% endif %}>企業型年金・個人型年金加入者掛金</option>
          <option value="14-3"{% if '14-3' == post.colnumber %} selected{% endif %}>心身障害者扶養共済制度に関する掛金</option>
        </optgroup>
        <optgroup label="生命保険料">
          <option value="15-1"{% if '15-1' == post.colnumber %} selected{% endif %}>新生命保険料</option>
          <option value="15-2"{% if '15-2' == post.colnumber %} selected{% endif %}>旧生命保険料</option>
          <option value="15-3"{% if '15-3' == post.colnumber %} selected{% endif %}>新個人年金保険料</option>
          <option value="15-4"{% if '15-4' == post.colnumber %} selected{% endif %}>旧個人年金保険料</option>
          <option value="15-5"{% if '15-5' == post.colnumber %} selected{% endif %}>介護医療保険料</option>
        </optgroup>
        <optgroup label="地震保険料等">
          <option value="16-1"{% if '16-1' == post.colnumber %} selected{% endif %}>地震保険料等</option>
          <option value="16-2"{% if '16-2' == post.colnumber %} selected{% endif %}>旧長期損害保険料</option>
        </optgroup>
      </select>
    </div>
    <div class="fieldset">
      <label>保険の種類・名称等</label>
      <input type="text" name="title" value="{{ post.title }}" required>
    </div>
    <div class="fieldset">
      <label>対象年度</label>
      <select name="year" class="short" required>
        {% for year in years %}
        <option value="{{ year }}"{% if year == post.year %} selected{% endif %}>{{ year }}年</option>
        {% endfor %}
      </select>
    </div>
    <div class="fieldset">
      <label>控除額</label>
      <input type="text" name="amount" value="{{ post.amount }}" class="short ta-r" required>
    </div>
    <div class="fieldset">
      <label>添付ファイル</label>
      <div class="input">
        <input type="file" name="file" id="file" accept=".pdf,application/pdf,.eml,message/rfc822">
        <input type="hidden" name="category" value="控除証明書">
        <input type="hidden" name="source" value="紙媒体（スキャン／撮影）">
      </div>
    </div>
    <div class="form-footer">
      <div class="separate-block">
        <span>
          <input type="submit" name="s1_submit" value="保存">
        </span>
      </div>
    </div>

    {% for unit in list %}
      {% if loop.first %}
      <hr>
      <h2>登録済リスト</h2>
      <table class="data-list">
      {% endif %}
        <tr data-detail="{{ unit.json }}">
          <td>{{ unit.year }}<small>年</small></td>
          <td>{{ unit.title }}</td>
          <td><i>{{ unit.amount|number_format }}</i><small>円</small></td>
          <td><a href="#remove:{{ unit.id }}" class="remove" data-confirmation="{{ unit.title }}を削除します。よろしいですか？">削除</a></td>
        </tr>
      {% if loop.last %}
      </table>
      {% endif %}
    {% endfor %}
  </div>
{% endblock %}

{% block pagefooter %}
<script src="/script/oas/social_insurance.js"></script>
{% endblock %}
