{% for sender in senders %}
  {% if loop.first %}
  <ul id="suggested-senders">
  {% endif %}
    <li>
      <a href="#" data-sender="{{ sender.sender }}">{{ sender.sender }}</a>
    </li>
  {% if loop.last %}
  </ul>
  {% endif %}
{% endfor %}
