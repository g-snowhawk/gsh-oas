{% extends "subform.tpl" %}

{% block main %}
  <input type="hidden" name="rel" value="{{ rel }}">
  <input type="hidden" name="redirect_mode">
  {% set appname = apps.currentApp('basename') %}
  <article class="wrapper">
    <h1>ファイルアップロード</h1>
    {% if err.vl_file >= 1 %}
      <div class="error">
        {% if err.vl_file == 4 %}
          <i>ファイルを選択してください</i>
        {% elseif err.vl_file == 1 or err.vl_file == 2 %}
          <i>ファイルサイズが大き過ぎます</i>
        {% elseif err.vl_file == 9 %}
          <i>PDFファイルを選択してください</i>
        {% elseif err.vl_file == 128 %}
          <i>同じファイルが存在します</i>
        {% else %}
          <i>ファイルアップロードに失敗しました</i>
        {% endif %}
      </div>
      <div class="error">
      </div>
    {% endif %}
    <div class="fieldset{% if err.vl_file == 1 %} invalid{% endif %}">
      <label for="file">ファイル<small>&nbsp;※PDF、EMLのみ選択可</small></label>
      <input type="file" name="file" id="file" accept=".pdf,application/pdf,.eml,message/rfc822" required>
    </div>

    <div class="fieldset">
      <label for="source">受領形態</label>
      <select name="source" id="source" required>
        <option></option>
        <option value="WEB（ダウンロード）"{% if post.source == 'WEB（ダウンロード）' %} selected{% endif %}>WEB（ダウンロード）</option>
        <option value="WEB（画面キャプチャ）"{% if post.source == 'WEB（画面キャプチャ）' %} selected{% endif %}>WEB（画面キャプチャ）</option>
        <option value="メール（本文）"{% if post.source == 'メール（本文）' %} selected{% endif %}>メール（本文）</option>
        <option value="メール（添付）"{% if post.source == 'メール（添付）' %} selected{% endif %}>メール（添付）</option>
        <option value="紙媒体（スキャン／撮影）"{% if post.source == '紙媒体（スキャン／撮影）' %} selected{% endif %}>紙媒体（スキャン／撮影）</option>
        <option value="その他"{% if post.source == 'その他' %} selected{% endif %}>その他</option>
      </select>
    </div>

    {% if err.vl_receipt_date >= 1 %}
      <div class="error">
        {% if err.vl_receipt_date == 2 %}
          <i>本日以前を選択のこと</i>
        {% else %}
          <i>日付を選択してください</i>
        {% endif %}
      </div>
      <div class="error">
      </div>
    {% endif %}
    <div class="fieldset">
      <label for="receipt_date">日付</label>
      <input type="date" name="receipt_date" id="receipt_date" value="{{ post.receipt_date }}" required>
    </div>

    <div class="fieldset" id="fs-price">
      <label for="price">金額</label>
      <input type="number" name="price" id="price" value="{{ post.price }}" placeholder="総額" required>
      <input type="number" name="tax_a" id="tax_a" value="{{ post.tax_a }}" placeholder="うち消費税10%" class="tax">
      <input type="number" name="tax_b" id="tax_b" value="{{ post.tax_b }}" placeholder="うち消費税8%" class="tax">
    </div>

    <div class="fieldset">
      <label for="sender">取引先</label>
      <input type="text" name="sender" id="sender" value="{{ post.sender }}" required>
      <div id="sender-data"></div>
    </div>

    <div class="fieldset">
      <label for="category">種別</label>
      <select name="category" id="category" required>
        <option></option>
        <option value="見積書"{% if post.category == '見積書' %} selected{% endif %}>見積書</option>
        <option value="注文書"{% if post.category == '注文書' %} selected{% endif %}>注文書</option>
        <option value="注文請書"{% if post.category == '注文請書' %} selected{% endif %}>注文請書</option>
        <option value="納品書"{% if post.category == '納品書' %} selected{% endif %}>納品書</option>
        <option value="請求書"{% if post.category == '請求書' %} selected{% endif %}>請求書</option>
        <option value="領収書"{% if post.category == '領収書' %} selected{% endif %}>領収書</option>
        <option value="利用明細"{% if post.category == '利用明細' %} selected{% endif %}>利用明細</option>
        <option value="控除証明書"{% if post.category == '控除証明書' %} selected{% endif %}>控除証明書</option>
        <option value="その他"{% if post.category == 'その他' %} selected{% endif %}>その他</option>
      </select>
    </div>

    <div class="form-footer">
      <input type="submit" name="s1_submit" value="アップロード">
      <input type="hidden" name="mode" value="{{ appname }}.accepted-docs.receive:save">
    </div>
  </article>
{% endblock %}
