import { time, loadFixture } from "@nomicfoundation/hardhat-toolbox/network-helpers";
import { anyValue } from "@nomicfoundation/hardhat-chai-matchers/withArgs";
import { expect } from "chai";
import hre from "hardhat";

describe("BlockFunders", function () {
  // We define a fixture to reuse the same setup in every test.
  // We use loadFixture to run this setup once, snapshot that state,
  // and reset Hardhat Network to that snapshot in every test.
  async function deployBlockFundersFixture() {
    const [owner, donor] = await hre.ethers.getSigners();
    const BlockFunders = await hre.ethers.getContractFactory("BlockFunders");
    const blockFunders = await BlockFunders.deploy();
    return { blockFunders, owner, donor };
  }

  describe("createCampaign", function () {
    it("Should create a campaign successfully", async function () {
      const { blockFunders, owner } = await loadFixture(deployBlockFundersFixture);

      const beforeCount = await blockFunders.numOfCampaigns();
      expect(beforeCount).to.equal(0);

      const tx = await blockFunders.createCampaign(
        1,
        owner.address,
        "Help Plant Trees",
        "Tree planting initiative in Amazon.",
        hre.ethers.parseEther("10"), // 10 ETH
        (await time.latest()) + 86400, // 1 day from now
        "https://example.com/image.jpg"
      );

      const afterCount = await blockFunders.numOfCampaigns();
      expect(afterCount).to.equal(1);

      await expect(tx)
        .to.emit(blockFunders, "CampaignCreated")
        .withArgs(anyValue, owner.address, "Help Plant Trees", hre.ethers.parseEther("10"), anyValue);
    });

    it("Should revert with DeadlineInvalid error when creating a campaign with past deadline", async function () {
      const { blockFunders, owner } = await loadFixture(deployBlockFundersFixture);

      const pastDeadline = (await time.latest()) - 100; // A time in the past

      await expect(blockFunders.createCampaign(
        1,
        owner.address,
        "Expired Campaign",
        "This campaign should fail.",
        hre.ethers.parseEther("1"),
        pastDeadline,
        "https://example.com/expired.jpg"
      )).to.be.revertedWithCustomError(blockFunders, "DeadlineInvalid");
    });

  });

  describe("donateToCampaign", function () {
    it("Should accept donations and transfer funds", async function () {
      const { blockFunders, owner, donor } = await loadFixture(deployBlockFundersFixture);

      await blockFunders.createCampaign(
        1,
        owner.address,
        "Save the Ocean",
        "Ocean cleanup project.",
        hre.ethers.parseEther("5"), // 5 ETH target
        (await time.latest()) + 86400, // 1 day from now
        "https://example.com/ocean.jpg"
      );

      await expect(
        await blockFunders.connect(donor).donateToCampaign(1, {
          value: hre.ethers.parseEther("1"), // Send 1 ETH
        })  
      ).to.changeEtherBalances([donor, owner], [hre.ethers.parseEther("-1"), hre.ethers.parseEther("1")]);

      const [donators, donations] = await blockFunders.getDonators(1);
      expect(donators[0]).to.equal(donor.address);
      expect(donations[0]).to.equal(hre.ethers.parseEther("1"));
    });

    it("Should emit DonationReceived event when a donation is made", async function () {
      const { blockFunders, owner, donor } = await loadFixture(deployBlockFundersFixture);

      await blockFunders.createCampaign(
        1,
        owner.address,
        "Save the Ocean",
        "Ocean cleanup project.",
        hre.ethers.parseEther("5"), // 5 ETH target
        (await time.latest()) + 86400, // 1 day from now
        "https://example.com/ocean.jpg"
      );

      await expect(blockFunders.connect(donor).donateToCampaign(1, {
        value: hre.ethers.parseEther("1") // Send 1 ETH
      }))
      .to.emit(blockFunders, 'DonationReceived')
      .withArgs(1, donor.address, hre.ethers.parseEther("1"));
    });

  });
});
