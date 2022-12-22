{% for unit in summary %}
  {% if loop.first %}
  <ul id="suggested-summaries">
  {% endif %}
    <li>
      <a href="#" data-suggest="{{ unit.summary }}">{{ unit.summary }}</a>
    </li>
  {% if loop.last %}
  </ul>
  {% endif %}
{% endfor %}
