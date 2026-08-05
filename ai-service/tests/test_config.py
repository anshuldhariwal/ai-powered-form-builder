from app.config import Settings


def test_form_schema_limit_defaults_match_laravel() -> None:
    settings = Settings(_env_file=None)

    assert settings.form_max_schema_bytes == 1_048_576
    assert settings.form_max_steps == 20
    assert settings.form_max_sections_per_step == 30
    assert settings.form_max_fields == 150
    assert settings.form_max_options_per_field == 100
    assert settings.form_max_conditions == 300
