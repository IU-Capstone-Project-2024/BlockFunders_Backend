import { expect } from 'chai'
import hre from 'hardhat'


describe('BlockFundersNFT', function () {
    it('Should mint a new NFT', async function () {
        const BlockFundersNFT = await hre.ethers.getContractFactory('BlockFundersNFT')
        const blockFundersNFT = await BlockFundersNFT.deploy()
        const [owner] = await hre.ethers.getSigners()
        await blockFundersNFT.mint(
            owner.address,
            1,
            'https://example.com/token_metadata.json'
        )
        const balance = await blockFundersNFT.balanceOf(owner.address)
        expect(balance).to.equal(1)
        expect(await blockFundersNFT.tokenURI(1)).to.equal(
            'https://example.com/token_metadata.json'
        )
    })
})
