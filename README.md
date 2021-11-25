# Vires.Finance security audit

[По-русски](README.ru.md) | **In english**

- [Survey scope](viresfinance-security-audit.en.md#survey-scope)
- [Project composition](viresfinance-security-audit.en.md#project-composition)
- [Security](viresfinance-security-audit.en.md#security)
  - [General](viresfinance-security-audit.en.md#general)
  - [Administration](viresfinance-security-audit.en.md#administration)
- [Architecture](viresfinance-security-audit.en.md#architecture)
  - [RESERVE](viresfinance-security-audit.en.md#reserve)
  - [MAIN](viresfinance-security-audit.en.md#main)
  - [SETTINGS](viresfinance-security-audit.en.md#settings)
- [Threat model](viresfinance-security-audit.en.md#threat-model)
  - [Potential arguments/injection attacks](viresfinance-security-audit.en.md#potential-argumentsinjection-attacks)
  - [Potential amplification attacks](viresfinance-security-audit.en.md#potential-amplification-attacks)
- [Other recommendations](viresfinance-security-audit.en.md#other-recommendations)
- [Conclusion](viresfinance-security-audit.en.md#conclusion)

# Support scripts
- The scripts consolidated at [test](test) folder of this repo
- The scripts based on [WavesKit](https://github.com/deemru/WavesKit) functionality
- Use `composer install` to setup dependencies
- It is recommended to run a local Waves node with REST API (default is 127.0.0.1:6869)
- You can run the scripts in any order