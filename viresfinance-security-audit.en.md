# Vires.Finance security audit

(Translated by community)

- [Vires.Finance security audit](#viresfinance-security-audit)
  - [Survey scope](#survey-scope)
  - [Project composition](#project-composition)
  - [Security](#security)
    - [General](#general)
    - [Administration](#administration)
  - [Architecture](#architecture)
    - [RESERVE](#reserve)
    - [MAIN](#main)
    - [SETTINGS](#settings)
  - [Threat model](#threat-model)
    - [Potential arguments/injection attacks](#potential-argumentsinjection-attacks)
    - [Potential amplification attacks](#potential-amplification-attacks)
  - [Other recommendations](#other-recommendations)
  - [Conclusion](#conclusion)

## Survey scope

The Vires.Finance project is investigated up to and including November 8, 2021.

The object of the study is the security of the project on the Waves Mainnet.

At the time of the study, the project is an ecosystem consisting of 13 smart contracts and an administrator account. The external dependencies of this ecosystem are the functionality of price oracles and the staking of WAVES, USDN, and EURN tokens.

Since the USDN and EURN price oracle and staking functionality is the responsibility of [Neutrino Protocol](https://neutrino.at), the WAVES staking distribution is the responsibility of the node owners, these subsystems are not part of the audit.

## Project composition

The composition of the project is determined by the public information presented on the official website [Vires.Finance](https://vires.finance) and additional information from the development team, including the actual source codes of the smart contracts and the corresponding deployment addresses at Waves network.

The final set of contracts:

```php
$main = [
    ['main',            '3PAZv9tgK1PX7dKR7b4kchq5qdpUS3G5sYT'],
    ['settings',        '3PJ1kc4EAPL6fxuz3UZL68LPz1G9u4ptjYT'],
    ['oracle-proxy',    '3PFHm5TYKw4vVzj4rW8s3Yso88aD73Dai1C'],
];

$stakers = [
    ['staker-waves',    '3PMHsJn1G4ngd6A4dyZpaSMiQmr4XJiDuym'],
    ['staker-usdn',     '3P23drfMhqqouvzpt3xUyGwjVX8P8qAzrmi'],
    ['staker-eurn',     '3PH9oV2vraW7z7BxbMjHjcCMg3dmBKmUyhh'],
];

$reserves = [
    ['reserve',         '3P8G747fnB1DTQ4d5uD114vjAaeezCW4FaM'], // WAVES
    ['reserve',         '3PCwFXSq8vj8iKitA5zrrLRbuqehfmimpce'], // USDN
    ['reserve',         '3PEiD1zJWTMZNWSCyzhvBw9pxxAWeEwaghR'], // USDT
    ['reserve',         '3PGCkrHBxFMi7tz1xqnxgBpeNvn5E4M4g8S'], // USDC
    ['reserve',         '3PBjqiMwwag72VWUtHNnVrxTBrNK8D7bVcN'], // EURN
    ['reserve',         '3PA7QMFyHMtHeP66SUQnwCgwKQHKpCyXWwd'], // BTC
    ['reserve',         '3PPdeWwrzaxqgr6BuReoF3sWfxW8SYv743D'], // ETH
];

$vires = [
    ['vires-earlybirds',    '3PMqStMdARUA1KDNSrknUkQgXBVJR9Kgxko'],
    ['vires-minter',        '3PM9SV8qsubjwfxENgsLJvP1BG2Wc2VAd7b'],
    ['vires-staker',        '3PMrcFXJx23B9zbxxUT49z6ET6wF2dKfTdW'],
    ['vires-distributor',   '3P2RkFDTHJCB82HcVvJNU2eMEfUo82ZFagV'],
];

```
Smart contracts are discovered automatically using the [test-contracts](https://github.com/deemru/viresfinance-audit/blob/main/test/test-contracts.php) script. This script looks up the last smart contract installation transaction, decompiles the smart contract using the Waves node, and writes this information to a file. The file name contains a prefix, which is the checksum of the decompiled contract code.

According to the received data, the smart contracts can be represented as:

- MAIN (main routing contract)
- SETTINGS (settings)
- ORACLE (price provider)
- 3 STAKER (staking WAVES, USDN, and EURN with a standard interface)
- 7 RESERVE (accounting user deposits, debts, and reserves with a standard interface)
- VIRES-MINTER (issuing VIRES)
- VIRES-EARLYBIRDS (distribution of VIRES to early liquidity providers)
- VIRES-DISTRIBUTOR (distribution of VIRES for supply and borrow actions)
- VIRES-STAKER (distribution of VIRES in the form of staking)

## Security

### General

Vires.Finance smart contracts are considered from the point of view of user funds security in general, without regard to internal recalculation functionality.

Deposit and borrow information is maintained in [account data key-value-storages](https://docs.waves.tech/en/blockchain/account/account-data-storage) of the RESERVE type smart contracts, each smart contract has its non-overlapping data, which can be represented as a key/value pair database. In this case, user accounting keys, in addition to semantic prefixes and postfixes, always contain user addresses, which is equivalent to the typical storage of tokens in a blockchain, where the key in the blockchain database can be represented as the user address plus a token identifier. The value in both cases would be the token balance.

The token identifier is uniquely defined by the RESERVE address and does not change in the process.

The user address is determined by the sender address of the user transaction, which ensures that [all checks related to the user account](https://docs.waves.tech/en/ride/functions/verifier-function) are passed (e.g., additional to default signature verification actions, according to the user's smart account, if any).

There is functionality in contracts that can reduce user deposits. This negative impact on accounts occurs only if the user's debt exceeds his total deposit (`transferDebt()` and `forceCollapse()` functions). With no debt, or, more generally, with positive health under the health formula, there is no negative impact at all, except for the actions performed by the user, for example, when withdrawing funds.

There is no functionality in contracts to change the record owner, which explicitly locks the management of user funds by the user. Therefore, the closest analog of record-keeping at Vires.Finance contracts is the native operation of leasing, Lease, and Lease Cancel transactions in the Waves Mainnet.

### Administration

Automated contract analysis for fixed strings with values of predefined keys and addresses is performed by the [test-strings](https://github.com/deemru/viresfinance-audit/blob/main/test/test-strings.php) script. This script finds the latest smart contract deployment transaction, decompiles the smart contract using the Waves node, and finds all used data records corresponding to the public keys.

Automated analysis of the contracts data storages for keys with certain substrings is performed by the [test-data](https://github.com/deemru/viresfinance-audit/blob/main/test/test-data.php) script.

Analysis of strings fixed in contracts shows the absence of predefined public keys or control addresses. All contract settings are specified by initialization transactions in `initialize()` and `init()`, which define a contract with generic SETTINGS settings and an administrator address.

All contracts use a single administrator address, ADMIN.

All contracts, except ORACLE and STAKER, use a single shared SETTINGS address.

There is inconsistency in the logic of defining the administrator and lack of settings for some auxiliary contracts; in most contracts, the administrator is fixed directly in their repositories, but for RESERVE contracts and VIRES-EARLYBIRDS, the administrator is defined through the SETTINGS contract.

It is RECOMMENDED to unify the operation logic for defining the administrator only through the SETTINGS general settings contract, which will simplify the project initialization and administration logic in general.

There is no override of the [Verifier function](https://docs.waves.tech/en/ride/functions/verifier-function) in all contracts and at the administrator address, which means that the regular functionality of transaction checking in the Waves Mainnet is preserved. Thus, outgoing transactions on behalf of the contract and administrator address will be verified on the source keys of their respective addresses.

It is RECOMMENDED to improve the [key management system](https://en.wikipedia.org/wiki/Key_management), e.g., as part of the unification of SETTINGS logic, and to introduce a [multi-signature](https://en.wikipedia.org/wiki/Multisignature) scheme for all contracts.

At the moment, all contract addresses have only been used to set and update target contracts. The exception is one [data transaction](https://docs.waves.tech/en/blockchain/transaction-type/data-transaction) setting the `op_transfer_debt_paused` key to `true`. Information has been received from the development team that they are aware of the ability to set this flag by the regular administration functionality in the `resume()` function in the SETTINGS contract, but in this case, the most expeditious method was used to perform contract maintenance during an audit.

No centralized location with a record of administrative measures with the reasons for these actions has been found. While all actions in the Waves blockchain are open and auditable, it is RECOMMENDED to keep a public record of administrative measures and to consider allowing administrator actions through [DAO](https://en.wikipedia.org/wiki/Decentralized_autonomous_organization) mechanisms.

## Architecture

Vires.Finance smart contracts are considered from the point of view of architectural features aimed at leveling vulnerabilities in calling available functions.

### RESERVE

The RESERVE contracts maintain the direct storage of user tokens. If there is staking capability in a RESERVE contract, user tokens are atomically sent to the corresponding staking systems in their entirety through STAKER contracts. STAKER contracts are explicitly linked to the related RESERVE at initialization and do not contain features available to users other than RESERVE and ADMIN. The ability to withdraw funds in full in case of need is guaranteed by the standard functionality of the Waves Mainnet in the case of WAVES tokens and by the Neutrino protocol in the case of USDN and EURN.

Since the RESERVE contracts cannot have negative values during regular operation, when writing values to the repository, the `writeInt()` function is always used, which generates an error when any negative value occurs. This feature is a simple solution to ensure the regularity of all operations and exclude the class of potential vulnerabilities, facilitating the analysis.

Every time the deposit, debt, and reserve in RESERVE contracts change, the current accounting is synchronized in the `syncTotals()` function. This approach allows concentrating the main functionality in a commonplace, ensuring that all variations of increasing or decreasing the value of deposits and borrows are executed uniformly.

An index is used in the RESERVE contract to operate the functional increase values according to the protocol and settings in SETTINGS. The index can increase one time per block, as it is derived from the settings and the difference between the current block height of the Waves Mainnet and the height of the last call. This approach guarantees the 1-block incremental increases in deposit, debt, and reserve, which eliminates the possibility of receiving or losing any funds if their participation in the system took less than one block.

### MAIN

RESERVE contracts are executed through the MAIN contract, as they do not possess the completeness of user information and accounting for VIRES token distribution.

The MAIN contract does not possess or keep records of user funds while being the central place that summarizes the complete information about the user in the Vires.Finance system, which allows making decisions about user capabilities and allowing or denying specific actions.

Control of all actions that can have a negative impact on user account health, or, generally, on the system as a whole, is concentrated in the function `validateAfter()`, which performs a final audit of the user on all RESERVEs according to SETTINGS. This approach simplifies the execution logic, which in this case does not require additional pre-audits of the user scenarios. After applying all actions within a transaction in other contracts, the `validateAfter()` call ensures that the final user state is within the protocol; otherwise, it returns an error for the entire transaction.

### SETTINGS

As noted earlier, all key contracts are connected and depend on a centralized contract with SETTINGS settings.

The SETTINGS contract provides vital protocol configuration and is also the provider of permissions for the operation of specific scenarios, up to and including the point-by-point prohibition of specific functions within the dependent contracts. It uses a 3-level system for this purpose: 1) specific function pause for a specific token, 2) specific function pause 3) pause of all operations.

In this way, new, obsolete or problematic functionality can be paused without affecting the protocol as a whole. For instance, at the time of writing, the `atokens` functionality is disabled.

It is noticed that the names of the keys that allow/disable certain functionality contain the ending `_paused`, while the value of such key `true` is an enabling action. It is NOT RECOMMENDED to use the opposite meaning of key names and their meanings.

## Threat model

Vires.Finance smart contracts are examined in terms of vulnerabilities. Vulnerabilities are defined as any actions within the operation of contracts in the Waves Mainnet that can negatively affect the operation of the Vires.Finance protocol. In addition to the direct loss of funds, a negative impact includes unexpected results of the regular calculations and formulas.

Since the threats can only be manifested through transactions, the capabilities of a typical system user are considered. Threats to compromise the private keys of smart contracts and threats related to administrator actions are not considered due to the current state of [administration](#administration).

Transactions of a typical system user in the Waves network can affect the operation of current contracts only by changing their state, which is achieved by the presence of actions in the contract execution results. As a rule, these are the actions of writing a new value by key and token transfer by address. Thus, the threat analysis first locates the functions that contain the target outcome actions and the function calls that cause those actions.

Additionally, the primary analysis involves identifying all functions in the system available to a typical user that, at least in theory, may overlap with the localized set of functions with resulting actions.

This analysis shows that Vires.Finance's functionality available to a typical user does not go beyond the functionality declared by the protocol. All the service functions that imply interaction of contracts have a check of the caller's belonging to the system and correspond to the declared logic. Therefore, the set of functions for direct analysis is significantly reduced at this stage.

### Potential arguments/injection attacks

The target set of functions has been examined for crafting arguments, including the use of:
 - strings with non-standard addresses;
 - strings with abnormal token identifiers;
 - truncated/increased strings to hit keys other than the regular ones;
 - negative integer values;
 - integer values close to an integer overflow;
 - integer values at the boundaries of roundings in formulas.

It is discovered that [architectural features](#architecture) protect against most integer attacks. Issues with large numbers and numbers on the boundaries of roundings in the formulas have also not been detected.

The appropriate check functions (`validateReserve()` and `findReserveBy()`) handle attacks on strings with non-standard addresses and token identifiers; no problems are detected here as well.

Note that the `pauseAssetOp()` function, although not public, can write a `true` value on a non-standard key. It is RECOMMENDED to introduce an additional check on the `assetId` argument similar to public functions since access to this function can be extended to a group of so-called pause-administrators.

It has been discovered that in the VIRES-MINTER contract, despite the caller restrictions (`distributorOnly()` and `stakerOnly()`), the VIRES token release functions can contain unverified `recipient` values.

It is RECOMMENDED to limit the existing scenarios as much as possible since such variability requires additional analysis to confirm the correctness and additional responsibility from third-party contract developers. In this case, the purposes of the chosen implementation are not obvious.

### Potential amplification attacks

A study of the target set of functions for increasing/decreasing the state values was performed when using:
- A complex atomic call consisting of several calls to Vires.Finance contracts
- complex calls executed within one block

It has been discovered that [architectural features](#architecture) protect against all kinds of such attacks on VIRES deposit, debt, distribution, and staking.

One exception has been found: the public `realloc()` function in the VIRES-DISTRIBUTOR contract, which allows recalculating the relative distribution rate of VIRES tokens depending on the current debt values in RESERVE contracts. Since this function is not limited in call frequency, the user can recalculate the distribution rate at any point, including an artificially created moment of debt skew within an atomic call or a single block. As a result, there is an opportunity to influence the VIRES token distribution's relative speed artificially.

This attack is classified as an amplification attack. However, it does not affect the total number of tokens distributed per day and allows any other user to rebalance the distribution immediately after the attack.

The team is aware of the problem and will address it in future protocol versions by including this functionality in the general atomic execution script or by changing the public call rights for this feature.

## Other recommendations

It is RECOMMENDED to reimplement the `redeemAtokens()` function in RESERVE contracts (currently, the `atokens` functionality is disabled in SETTINGS) more generically. Similar to other `atokens` functions, they need to be invoked through the MAIN contract exclusively, which will perform the corresponding `streamChange` deposit correction in the `updateStream()` function.

## Conclusion

The implementation of Vires.Finance contracts is a solid example of the fundamental approach of dividing complex functionality into separate semantic parts. The considered [architectural features](#architecture) with high probability guarantee the absence of vulnerabilities at the time of writing and their occurrence in the future.

The project's main functionality is fully decentralized and requires no regular maintenance.

Recommendations regarding the current state of [administration](#administration) have been responded to by the team as the enhancements already planned for the nearest future.

Other issues found as part of the audit have been promptly corrected or leveled.

Other non-critical issues have been audited with recommendations that are not mandatory but should be taken into account and carefully considered before and in case of disregarding the recommendations in future versions of the project.
