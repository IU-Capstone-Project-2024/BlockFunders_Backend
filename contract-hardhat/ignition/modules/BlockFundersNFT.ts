import { buildModule } from '@nomicfoundation/hardhat-ignition/modules'

const Module = buildModule('BlockFundersNFTModule', m => {
    const contract = m.contract('BlockFundersNFT')

    return { contract }
})

export default Module
