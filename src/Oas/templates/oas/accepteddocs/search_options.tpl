{% set formposition = 'bottom' %}
{% extends "subform.tpl" %}

{% block main %}
<article class="wrapper">
  <h1>詳細検索条件</h1>
  <div class="flex">
    <div class="column">
      <div class="fieldset">
        <label for="sender">取引先</label>
        <div class="input flex">
          <input type="text" name="search_query" id="search-query-1" class="search-query"{% if queryString %} value="{{ queryString }}"{% endif %}>
        </div>
      </div>
      <div class="fieldset">
        <label for="receipt_date">種別</label>
        <div class="input flex">
          <select name="category" id="category">
            <option></option>
            <option value="見積書"{% if post.category == '見積書' %} selected{% endif %}>見積書</option>
            <option value="注文書"{% if post.category == '注文書' %} selected{% endif %}>注文書</option>
            <option value="注文請書"{% if post.category == '注文請書' %} selected{% endif %}>注文請書</option>
            <option value="納品書"{% if post.category == '納品書' %} selected{% endif %}>納品書</option>
            <option value="請求書"{% if post.category == '請求書' %} selected{% endif %}>請求書</option>
            <option value="領収書"{% if post.category == '領収書' %} selected{% endif %}>領収書</option>
            <option value="利用明細"{% if post.category == '利用明細' %} selected{% endif %}>利用明細</option>
            <option value="その他"{% if post.category == 'その他' %} selected{% endif %}>その他</option>
          </select>
        </div>
      </div>
      <div class="naked">
        <label><input type="radio" name="andor" id="andor-1" value="AND"{% if post.andor == "AND" %} checked{% endif %}>AND検索</label>
        <label><input type="radio" name="andor" id="andor-2" value="OR"{% if post.andor == "OR" %} checked{% endif %}>OR検索</label>
      </div>
    </div>
    <div class="column">
      <div class="fieldset">
        <label for="receipt_date">日付指定</label>
        <div class="input flex">
          <input type="date" name="receipt_date_start" id="receipt_date_start" value="{{ post.receipt_date_start is empty ? '' : post.receipt_date_start|date('Y-m-d') }}" class="grow-1">
          <em>〜</em>
          <input type="date" name="receipt_date_end" id="receipt_date_end" value="{{ post.receipt_date_end is empty ? '' : post.receipt_date_end|date('Y-m-d') }}" class="grow-1">
        </div>
      </div>
      <div class="fieldset">
        <label for="price">金額指定</label>
        <div class="input flex">
          <input type="number" name="price_min" id="price_min" value="{{ post.price_min is empty ? '' : post.price_min }}" class="grow-1">
          <em>〜</em>
          <input type="number" name="price_max" id="price_max" value="{{ post.price_max is empty ? '' : post.price_max }}" class="grow-1">
        </div>
      </div>
    </div>
  </div>

  <div class="form-footer">
    <input type="hidden" name="mode" value="oas.accepted-docs.receive:save-search-options">
    <input type="submit" name="s1_submit" value="保存">
    <input type="submit" name="s1_clear" value="クリア">
  </div>
</article>
{% endblock %}
