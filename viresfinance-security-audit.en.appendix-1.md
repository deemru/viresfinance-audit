# Vires.Finance security audit: Appendix 1

This appendix contains formal list of possible issues, their severity and whether they were detected within the smart contracts or not.

- [Vires.Finance security audit: Appendix 1](#viresfinance-security-audit-appendix-1)
  - [1. Basic coding bugs](#1-basic-coding-bugs)
    - [1.1 Constructor reinitialization](#11-constructor-reinitialization)
    - [1.2 Ownership takeover](#12-ownership-takeover)
    - [1.3 Overflows & underflows](#13-overflows--underflows)
    - [1.4 Reentrancy](#14-reentrancy)
    - [1.5 Money-giving bug](#15-money-giving-bug)
    - [1.6 Blackhole](#16-blackhole)
    - [1.7 Unauthorized self-destruct](#17-unauthorized-self-destruct)
    - [1.8 Revert DoS](#18-revert-dos)
    - [1.9 Unchecked external `invoke()`](#19-unchecked-external-invoke)
    - [1.10 (Unsafe) Use of predictable random](#110-unsafe-use-of-predictable-random)
    - [1.11 Overlap in DataEntry keys](#111-overlap-in-dataentry-keys)
    - [1.12 Complexity overflow](#112-complexity-overflow)
    - [1.13 Attached payment assetId not validated](#113-attached-payment-assetid-not-validated)
    - [1.14 Zero attached payment](#114-zero-attached-payment)
  - [2. Semantic consistency checks](#2-semantic-consistency-checks)

## 1. Basic coding bugs
### 1.1 Constructor reinitialization
 - Description: Whether someone can re-initialize a contract.
 - Result: Not found
 - Severity: Critical

### 1.2 Ownership takeover
 - Description: Whether someone can gain unauthorized administrative/safeguard permissions.
 - Result: Not found
 - Severity: Critical

### 1.3 Overflows & underflows
 - Description: Whether the contract has general overflow or underflow vulnerabilities.
 - Result: Not found
 - Severity: Critical

### 1.4 Reentrancy
 - Description: Whether the code can call back into the contract to change state.
 - Result: Not found
 - Severity: Critical

### 1.5 Money-giving bug
 - Description: Whether the contract returns funds to an arbitrary address.
 - Result: Not found
 - Severity: High

### 1.6 Blackhole
 - Description: Whether the contract locks tokens indefinitely.
 - Result: Not found
 - Severity: Critical

### 1.7 Unauthorized self-destruct
 - Description: Whether the contract can be killed by an arbitrary party.
 - Result: Not found
 - Severity: Medium

### 1.8 Revert DoS
 - Description: Whether the contract is vulnerable to DoS attack because of unexpected `throw`.
 - Result: Not found
 - Severity: Medium

### 1.9 Unchecked external `invoke()`
 - Description: Whether the contract has any external call relying on the return value.
 - Result: Fixed
 - Severity: Medium

### 1.10 (Unsafe) Use of predictable random
 - Description: Whether the contract contains a randomness which can be predicted.
 - Result: Not found
 - Severity: Medium

### 1.11 Overlap in DataEntry keys
 - Description: Whether the function's WriteSet has overlaps instead of aggregations.
 - Result: Not found
 - Severity: Medium

### 1.12 Complexity overflow
 - Description: Whether some scenarios are not possible under certain conditions due to complexity limit.
 - Result: Not found
 - Severity: Medium

### 1.13 Attached payment assetId not validated
 - Description: Whether the contract can accept one payment asset instead of the required.
 - Result: Not found
 - Severity: High
 
### 1.14 Zero attached payment
 - Description: Whether the contract invokes another contract attaching 0 of an asset rendering the invocation impossible.
 - Result: Not found
 - Severity: Medium

## 2. Semantic consistency checks
 - Description: Whether the semantic of whitepaper is different from the implementation of the contract.
 - Result: Not found
 - Severity: Critical
